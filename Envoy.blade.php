@setup
require __DIR__.'/vendor/autoload.php';

$branch = "main";
$server = "zrenie.clinic";
$userAndServer = 'forge@'. $server;
$repository = "dmitriypur/clinic2";
$baseDir = "/home/forge/zrenie.clinic";
$releasesDir = "{$baseDir}/releases";
$currentDir = "{$baseDir}/current";
$newReleaseName = date('Ymd-His');
$newReleaseDir = "{$releasesDir}/{$newReleaseName}";
$user = get_current_user();

function logMessage($message) {
return "echo '\033[32m" .$message. "\033[0m';\n";
}
@endsetup

@servers(['local' => '127.0.0.1', 'remote' => $userAndServer])

@macro('deploy')
startDeployment
cloneRepository
verifyEnvironment
runComposer
runNpm
generateAssets
updateSymlinks
optimizeInstallation
backupDatabase
migrateDatabase
blessNewRelease
cleanOldReleases
finishDeploy
@endmacro

@macro('deploy-code')
deployOnlyCode
@endmacro

@task('startDeployment', ['on' => 'local'])
{{ logMessage("🏃  Starting deployment...") }}
git checkout {{ $branch }}
git pull origin {{ $branch }}
@endtask

@task('cloneRepository', ['on' => 'remote'])
{{ logMessage("🌀  Cloning repository...") }}
[ -d {{ $releasesDir }} ] || mkdir {{ $releasesDir }};
cd {{ $releasesDir }}

# Create the release dir
mkdir {{ $newReleaseDir }}

# Clone the repo
git clone --depth 1 --branch {{ $branch }} git@github.com:{{ $repository }} {{ $newReleaseName }}

# Configure sparse checkout
cd {{ $newReleaseDir }}
git config core.sparsecheckout true
echo "*" > .git/info/sparse-checkout
echo "!storage" >> .git/info/sparse-checkout
echo "!public/build" >> .git/info/sparse-checkout
git read-tree -mu HEAD

# Mark release
cd {{ $newReleaseDir }}
echo "{{ $newReleaseName }}" > public/release-name.txt
@endtask

@task('verifyEnvironment', ['on' => 'remote'])
{{ logMessage("🔎  Verifying runtime versions...") }}
cd {{ $newReleaseDir }}

[ -f composer.json ] || { echo "composer.json отсутствует — прерываю деплой"; exit 1; }
[ -f package.json ] || { echo "package.json отсутствует — прерываю деплой"; exit 1; }
[ -f .nvmrc ] || { echo ".nvmrc отсутствует — прерываю деплой"; exit 1; }

EXPECTED_PHP=$(php -r '$composer = json_decode(file_get_contents("composer.json"), true); echo $composer["config"]["platform"]["php"] ?? "";')
EXPECTED_NODE=$(tr -d "[:space:]" < .nvmrc)
EXPECTED_NPM=$(php -r '$package = json_decode(file_get_contents("package.json"), true); $manager = $package["packageManager"] ?? ""; echo preg_replace("/^npm@/", "", $manager);')

[ -n "$EXPECTED_PHP" ] || { echo "Не удалось определить ожидаемую версию PHP из composer.json"; exit 1; }
[ -n "$EXPECTED_NODE" ] || { echo "Не удалось определить ожидаемую версию Node.js из .nvmrc"; exit 1; }
[ -n "$EXPECTED_NPM" ] || { echo "Не удалось определить ожидаемую версию npm из package.json"; exit 1; }

command -v php >/dev/null 2>&1 || { echo "php не найден — прерываю деплой"; exit 1; }
command -v node >/dev/null 2>&1 || { echo "node не найден — прерываю деплой"; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "npm не найден — прерываю деплой"; exit 1; }

ACTUAL_PHP=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . "." . PHP_RELEASE_VERSION;')
ACTUAL_NODE=$(node -v | sed 's/^v//')
ACTUAL_NPM=$(npm --version)

echo "Expected PHP:  $EXPECTED_PHP"
echo "Actual PHP:    $ACTUAL_PHP"
[ "$ACTUAL_PHP" = "$EXPECTED_PHP" ] || { echo "Версия PHP не совпадает — прерываю деплой"; exit 1; }

echo "Expected Node: $EXPECTED_NODE"
echo "Actual Node:   $ACTUAL_NODE"
[ "$ACTUAL_NODE" = "$EXPECTED_NODE" ] || { echo "Версия Node.js не совпадает — прерываю деплой"; exit 1; }

echo "Expected npm:  $EXPECTED_NPM"
echo "Actual npm:    $ACTUAL_NPM"
[ "$ACTUAL_NPM" = "$EXPECTED_NPM" ] || { echo "Версия npm не совпадает — прерываю деплой"; exit 1; }
@endtask

@task('runComposer', ['on' => 'remote'])
{{ logMessage("🚚  Running Composer...") }}
cd {{ $newReleaseDir }}
[ -f composer.lock ] || { echo "composer.lock отсутствует — прерываю деплой"; exit 1; }
composer install --prefer-dist --no-dev --no-progress --no-interaction --no-scripts --no-plugins -o
@endtask

@task('runNpm', ['on' => 'remote'])
{{ logMessage("📦  Running NPM...") }}
cd {{ $newReleaseDir }}
[ -f package-lock.json ] || { echo "package-lock.json отсутствует — прерываю деплой"; exit 1; }
npm ci
@endtask

@task('generateAssets', ['on' => 'remote'])
{{ logMessage("🌅  Generating assets...") }}
cd {{ $newReleaseDir }}
npm run build
@endtask

@task('updateSymlinks', ['on' => 'remote'])
{{ logMessage("🔗  Updating symlinks to persistent data...") }}
# Remove the storage directory and replace with persistent data
rm -rf {{ $newReleaseDir }}/storage;
cd {{ $newReleaseDir }};
ln -nfs {{ $baseDir }}/persistent/storage storage;

rm -rf {{ $newReleaseDir }}/public/storage;
cd {{ $newReleaseDir }}/public/;
ln -nfs {{ $baseDir }}/persistent/storage/app/public storage;

# Import the environment config
cd {{ $newReleaseDir }}
ln -nfs {{ $baseDir }}/.env .env
@endtask

@task('optimizeInstallation', ['on' => 'remote'])
{{ logMessage("✨  Optimizing installation...") }}
cd {{ $newReleaseDir }}
php artisan clear-compiled
@endtask

@task('backupDatabase', ['on' => 'remote'])
{{ logMessage("📀  Backing up database...") }}
cd {{ $newReleaseDir }}
php artisan backup:run
@endtask

@task('migrateDatabase', ['on' => 'remote'])
{{ logMessage("🙈  Migrating database...") }}
cd {{ $newReleaseDir }}
php artisan migrate --force
@endtask

@task('blessNewRelease', ['on' => 'remote'])
{{ logMessage("🙏  Blessing new release...") }}
ln -nfs {{ $newReleaseDir }} {{ $currentDir }}
cd {{ $newReleaseDir }}

php artisan sitemap:generate
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan event:cache

sudo service php8.1-fpm restart
sudo service supervisor restart all
@endtask

@task('cleanOldReleases', ['on' => 'remote'])
{{ logMessage("🚾  Cleaning up old releases...") }}
# Delete all but the 3 most recent.
cd {{ $releasesDir }}
ls -dt {{ $releasesDir }}/* | tail -n +4 | xargs -d "\n" sudo chown -R forge .
ls -dt {{ $releasesDir }}/* | tail -n +4 | xargs -d "\n" rm -rf
@endtask

@task('finishDeploy', ['on' => 'local'])
{{ logMessage("🚀  Application deployed!") }}
@endtask

@task('deployOnlyCode',['on' => 'remote'])
{{ logMessage("💻  Deploying code changes...") }}
cd {{ $currentDir }}
git pull origin {{ $branch }}
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan event:cache
sudo service php8.1-fpm restart
sudo service supervisor restart all
@endtask

@task('rollback', ['on' => 'remote'])
{{ logMessage("⏮  Rolling back to the previous release...") }}
cd {{ $releasesDir }}
# Get current release path
CURRENT=$(readlink -f {{ $currentDir }})
# Find previous release (newest folder that is NOT current)
PREVIOUS=$(ls -dt {{ $releasesDir }}/* | grep -vFx "$CURRENT" | head -n 1)

if [ -z "$PREVIOUS" ]; then
    echo "❌  No previous release found!"; exit 1;
fi

echo "Rolling back from $CURRENT to $PREVIOUS"
ln -nfs $PREVIOUS {{ $currentDir }}

# Reset caches for the restored version
cd $PREVIOUS
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan event:cache

# Restart services
sudo service php8.1-fpm restart
sudo service supervisor restart all

{{ logMessage("✅  Rollback successful!") }}
@endtask
