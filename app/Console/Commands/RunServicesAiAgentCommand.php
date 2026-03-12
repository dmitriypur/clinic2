<?php

namespace App\Console\Commands;

use App\Exceptions\ServiceIntegrationException;
use App\Services\ServiceIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;

class RunServicesAiAgentCommand extends Command
{
    protected $signature = 'app:services-ai-agent
        {instruction* : Текстовое задание для ИИ}
        {--apply : Реально применить изменения после dry-run}
        {--model= : Имя модели в Ollama}
        {--ollama-url= : Базовый URL Ollama}';

    protected $description = 'Запускает локального ИИ-агента для изменения услуг и цен через Ollama';

    public function handle(ServiceIntegrationService $serviceIntegrationService): int
    {
        $instruction = trim(implode(' ', (array) $this->argument('instruction')));
        $model = (string) ($this->option('model') ?: env('OLLAMA_MODEL', 'llama3.1:8b'));
        $ollamaUrl = rtrim((string) ($this->option('ollama-url') ?: env('OLLAMA_URL', 'http://127.0.0.1:11434')), '/');

        if ($instruction === '') {
            $this->error('Нужно передать текстовое задание для ИИ.');

            return self::FAILURE;
        }

        $this->info("Модель: {$model}");
        $this->line("Задание: {$instruction}");

        $tree = $serviceIntegrationService->getTree(true);
        $catalog = $this->buildCatalogForPrompt($tree['services']);
        $cities = $tree['cities'] ?? [];

        try {
            $agentResponse = $this->askOllama($instruction, $catalog, $cities, $ollamaUrl, $model);
        } catch (ConnectionException $exception) {
            $this->error("Не удалось подключиться к Ollama по адресу {$ollamaUrl}.");
            $this->line('Убедитесь, что запущен `ollama serve`.');

            return self::FAILURE;
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (($agentResponse['need_clarification'] ?? false) === true) {
            $this->warn('ИИ запросил уточнение и ничего не менял.');
            $this->line((string) ($agentResponse['message'] ?? 'Нужно уточнение.'));

            return self::SUCCESS;
        }

        $operations = $agentResponse['operations'] ?? null;

        if (! is_array($operations) || $operations === []) {
            $this->error('ИИ не вернул ни одной операции.');
            $this->line(json_encode($agentResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::FAILURE;
        }

        $operations = $this->normalizeOperations($operations, $catalog);

        $this->newLine();
        $this->info('Операции, которые предложил ИИ:');
        $this->line(json_encode($operations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        try {
            $dryRun = $serviceIntegrationService->applyOperations($operations, true);
        } catch (ServiceIntegrationException $exception) {
            $this->error('Dry-run завершился ошибкой.');
            $this->line($exception->getMessage());
            $this->line(json_encode($exception->context(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Dry-run результат:');
        $this->line(json_encode($dryRun, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (! $this->option('apply')) {
            $this->comment('Изменения не применены. Для реальной записи запустите команду с флагом --apply.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Применить эти изменения в базу?')) {
            $this->comment('Применение отменено.');

            return self::SUCCESS;
        }

        try {
            $applied = $serviceIntegrationService->applyOperations($operations, false);
        } catch (ServiceIntegrationException $exception) {
            $this->error('Применение завершилось ошибкой.');
            $this->line($exception->getMessage());
            $this->line(json_encode($exception->context(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Изменения применены:');
        $this->line(json_encode($applied, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    protected function askOllama(string $instruction, array $catalog, array $cities, string $ollamaUrl, string $model): array
    {
        $response = Http::timeout(120)
            ->acceptJson()
            ->post("{$ollamaUrl}/api/chat", [
                'model' => $model,
                'stream' => false,
                'format' => 'json',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildUserPrompt($instruction, $catalog, $cities),
                    ],
                ],
                'options' => [
                    'temperature' => 0,
                ],
            ])
            ->throw();

        $content = data_get($response->json(), 'message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Ollama вернула пустой ответ.');
        }

        return $this->decodeJsonResponse($content);
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
Ты преобразуешь текстовые задания менеджера в JSON-операции для изменения услуг и цен на сайте.

Правила:
1. Отвечай только JSON-объектом без markdown и без пояснений.
2. Формат ответа:
{
  "need_clarification": boolean,
  "message": string|null,
  "operations": array
}
3. Если задача неоднозначна или невозможно точно определить нужную услугу, верни:
{
  "need_clarification": true,
  "message": "короткий вопрос на русском",
  "operations": []
}
4. Если всё понятно, верни "need_clarification": false и массив operations.
5. Разрешённые типы операций:
   - create_service
   - update_service
   - delete_service
   - upsert_price
   - delete_price
6. Для существующих услуг используй только service_uuid и parent_uuid из переданного каталога.
7. Для новых услуг в рамках одного запроса используй ref, а затем service_ref или parent_ref.
8. Не придумывай UUID.
9. Если пользователь просит удалить родительскую услугу вместе с подуслугами, укажи cascade_children=true.
10. Если речь идёт о цене без города, используй city_slug = null.
11. Если пользователь просит ограничить услугу или подуслугу по городам, используй поле city_slugs.
12. Если пользователь просит разные цены для разных городов, создай несколько операций upsert_price с разными city_slug.
13. Используй только города и slug из переданного списка доступных городов.
PROMPT;
    }

    protected function buildUserPrompt(string $instruction, array $catalog, array $cities): string
    {
        $catalogJson = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $citiesJson = json_encode($cities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Задание менеджера:
{$instruction}

Доступные города:
{$citiesJson}

Текущий каталог услуг:
{$catalogJson}
PROMPT;
    }

    protected function buildCatalogForPrompt(array $services): array
    {
        return array_map(function (array $service): array {
            return [
                'uuid' => $service['uuid'],
                'title' => $service['title'],
                'is_active' => $service['is_active'],
                'sort_order' => $service['sort_order'],
                'city_slugs' => $service['city_slugs'],
                'prices' => $service['prices'],
                'children' => array_map(function (array $child): array {
                    return [
                        'uuid' => $child['uuid'],
                        'title' => $child['title'],
                        'is_active' => $child['is_active'],
                        'sort_order' => $child['sort_order'],
                        'city_slugs' => $child['city_slugs'],
                        'prices' => $child['prices'],
                    ];
                }, $service['children']),
            ];
        }, $services);
    }

    protected function decodeJsonResponse(string $content): array
    {
        $normalized = trim($content);

        if (str_starts_with($normalized, '```')) {
            $normalized = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $normalized) ?? $normalized;
            $normalized = trim($normalized);
        }

        try {
            $decoded = json_decode($normalized, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException('Не удалось разобрать JSON-ответ от Ollama: '.$exception->getMessage());
        }

        if (! is_array($decoded)) {
            throw new \RuntimeException('Ollama вернула некорректный JSON-объект.');
        }

        return $decoded;
    }

    protected function normalizeOperations(array $operations, array $catalog): array
    {
        $normalized = [];
        $lastCreatedRef = null;
        $createIndex = 1;
        $knownUuids = $this->extractKnownUuids($catalog);
        $createdTitleToRef = [];

        foreach ($operations as $operation) {
            if (! is_array($operation)) {
                continue;
            }

            $type = $operation['type'] ?? null;

            if (! is_string($type) || $type === '') {
                continue;
            }

            $payload = $operation;

            if (isset($operation['data']) && is_array($operation['data'])) {
                $payload = array_merge($operation['data'], ['type' => $type]);
            }

            $payload = $this->normalizeCreateServicePayload($payload);

            if (($payload['parent_uuid'] ?? null) === 'ref') {
                unset($payload['parent_uuid']);
                if ($lastCreatedRef) {
                    $payload['parent_ref'] = $payload['parent_ref'] ?? $lastCreatedRef;
                }
            }

            if (($payload['service_uuid'] ?? null) === 'ref') {
                unset($payload['service_uuid']);
                if ($lastCreatedRef) {
                    $payload['service_ref'] = $payload['service_ref'] ?? $lastCreatedRef;
                }
            }

            if (isset($payload['parent_uuid']) && is_string($payload['parent_uuid']) && $this->looksLikeUuid($payload['parent_uuid']) && ! in_array($payload['parent_uuid'], $knownUuids, true)) {
                unset($payload['parent_uuid']);
            }

            if (isset($payload['parent_ref']) && is_string($payload['parent_ref'])) {
                if (isset($createdTitleToRef[$payload['parent_ref']])) {
                    $payload['parent_ref'] = $createdTitleToRef[$payload['parent_ref']];
                } elseif ($this->looksLikeUuid($payload['parent_ref'])) {
                    if (in_array($payload['parent_ref'], $knownUuids, true)) {
                        $payload['parent_uuid'] = $payload['parent_ref'];
                    }

                    unset($payload['parent_ref']);
                }
            }

            if (isset($payload['service_ref']) && is_string($payload['service_ref'])) {
                if (isset($createdTitleToRef[$payload['service_ref']])) {
                    $payload['service_ref'] = $createdTitleToRef[$payload['service_ref']];
                } elseif ($this->looksLikeUuid($payload['service_ref'])) {
                    if (in_array($payload['service_ref'], $knownUuids, true)) {
                        $payload['service_uuid'] = $payload['service_ref'];
                    }

                    unset($payload['service_ref']);
                }
            }

            if (isset($payload['service_uuid']) && is_string($payload['service_uuid']) && $this->looksLikeUuid($payload['service_uuid']) && ! in_array($payload['service_uuid'], $knownUuids, true)) {
                unset($payload['service_uuid']);

                if ($lastCreatedRef) {
                    $payload['service_ref'] = $payload['service_ref'] ?? $lastCreatedRef;
                }
            }

            if (($payload['parent_ref'] ?? null) === 'ref' && $lastCreatedRef) {
                $payload['parent_ref'] = $lastCreatedRef;
            }

            if (($payload['service_ref'] ?? null) === 'ref' && $lastCreatedRef) {
                $payload['service_ref'] = $lastCreatedRef;
            }

            if ($type === 'create_service') {
                $payload['ref'] = $payload['ref'] ?? "generated_ref_{$createIndex}";
                $lastCreatedRef = $payload['ref'];
                if (isset($payload['title']) && is_string($payload['title']) && $payload['title'] !== '') {
                    $createdTitleToRef[$payload['title']] = $payload['ref'];
                }
                $createIndex++;
            }

            unset($payload['data']);

            $normalized[] = $payload;
        }

        return $normalized;
    }

    protected function normalizeCreateServicePayload(array $payload): array
    {
        if (($payload['type'] ?? null) !== 'create_service') {
            return $payload;
        }

        if ((! isset($payload['title']) || ! is_string($payload['title']) || trim($payload['title']) === '')
            && isset($payload['service_ref'])
            && is_string($payload['service_ref'])
            && ! $this->looksLikeUuid($payload['service_ref'])) {
            $payload['title'] = trim($payload['service_ref']);
            unset($payload['service_ref']);
        }

        return $payload;
    }

    protected function extractKnownUuids(array $catalog): array
    {
        $uuids = [];

        foreach ($catalog as $service) {
            if (isset($service['uuid']) && is_string($service['uuid'])) {
                $uuids[] = $service['uuid'];
            }

            foreach ($service['children'] ?? [] as $child) {
                if (isset($child['uuid']) && is_string($child['uuid'])) {
                    $uuids[] = $child['uuid'];
                }
            }
        }

        return array_values(array_unique($uuids));
    }

    protected function looksLikeUuid(string $value): bool
    {
        return preg_match('/^[0-9a-fA-F-]{36}$/', $value) === 1;
    }
}
