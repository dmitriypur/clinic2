@setup
    $deployKey = getenv('ZRENIE_DEPLOY_KEY') ?: getenv('HOME').'/.ssh/zrenie-clinic-deploy';
    $key = escapeshellarg($deployKey);
    $ssh = 'ssh -i '.$key.' -o IdentitiesOnly=yes -o BatchMode=yes -o ConnectTimeout=8 -o StrictHostKeyChecking=yes zrenietest@147.45.184.229';
@endsetup

@servers(['local' => '127.0.0.1'])

@macro('deploy')
    startDeployment
    deployRelease
@endmacro

@macro('deploy-code')
    startDeployment
    deployRelease
@endmacro

@task('requireMain')
    if [ "$(git symbolic-ref --quiet --short HEAD)" != main ]; then
        echo 'Deploy must be run from the checked-out main branch.' >&2
        exit 1
    fi
@endtask

@task('startDeployment', ['on' => 'local'])
    @run('requireMain')
    git pull --ff-only origin main
@endtask

{{-- The stage tasks run together in one SSH session under the server's deployment lock. --}}
@task('releaseEnvironment')
    set -euo pipefail
    umask 022

    base=/var/www/zrenie-test
    releases="$base/releases"
    php=/usr/local/bin/zrenie-test-php
    host=zrenie.clinic
    release=''
    backup_tmp=''
    switching=0

    fail() {
        printf '%s\n' "$*" >&2
        exit 1
    }

    atomic_link() {
        local name=$1 target=$2
        ln -s -- "$target" "$work/$name"
        mv -Tf -- "$work/$name" "$base/$name"
    }

    ready_release() {
        local target=$1
        [[ -n "$target" && "${target%/*}" == "$releases" && ! -L "$target" ]] &&
            test -s "$target/.release-sha" &&
            test -f "$target/vendor/autoload.php" &&
            test -s "$target/public/build/manifest.json"
    }

    cleanup() {
        local status=$?
        trap - EXIT HUP INT TERM
        set +e

        if (( switching )) && [[ "$(readlink "$base/current")" != "$active" ]]; then
            rm -f -- "$work/current"
            if atomic_link current "$active"; then
                (cd "$active" && "$php" artisan queue:restart --no-interaction) ||
                    echo 'Could not signal the queue after restoring the previous release.' >&2
                printf 'Restored %s\n' "$active" >&2
            else
                echo 'Could not restore current. Check the release links on the server.' >&2
            fi
        fi

        if (( status != 0 )) && [[ -n "$release" && "$(readlink "$base/current")" != "$release" ]]; then
            rm -rf -- "$release"
        fi

        if [[ -n "$backup_tmp" ]]; then
            rm -f -- "$backup_tmp"
        fi
        rm -rf -- "$work"
        exit "$status"
    }

    initialize_release() {
        test "$(id -un)" = zrenietest || fail 'Unexpected deploy user.'
        exec 9>"$base/.deploy.lock"
        flock -x 9
        active=$(readlink "$base/current") || fail 'Could not read the active release.'
        ready_release "$active" || fail "Active release is incomplete: $active"
        work=$(mktemp -d "$base/.envoy-XXXXXX")
        trap cleanup EXIT
        trap 'exit 1' HUP INT TERM
    }
@endtask

@task('unpackSource')
    unpack_source() {
        local sha=$1 available
        [[ "$sha" =~ ^[a-f0-9]{40}$ ]] || fail 'Expected a commit SHA.'
        available=$(df -Pk "$base" | awk 'NR == 2 { print $4 }')
        test "$available" -ge 1800000 || fail 'Not enough disk space for a release and a database backup.'

        release=$(mktemp -d "$releases/$(date -u +%Y%m%d-%H%M%S)-${sha:0:12}-XXXXXX")
        chmod 755 "$release"
        version=${release##*/}
        printf 'Unpacking main at %s into %s\n' "$sha" "$version"
        tar -xf - -C "$release"
        cd "$release"
        test -f composer.lock && test -f package-lock.json || fail 'Dependency lock files are missing.'
        printf '%s\n' "$version" > public/release-name.txt
    }
@endtask

@task('updateSymlinks')
    update_symlinks() {
        echo 'Linking shared configuration and storage.'
        [[ ! -e .env && ! -L .env ]] || fail 'The release archive must not contain .env.'
        test -r "$base/.env"
        test -d "$base/persistent/storage/app/public"
        rm -rf -- storage public/storage
        ln -s -- "$base/persistent/storage" storage
        ln -s -- "$base/persistent/storage/app/public" public/storage
        ln -s -- "$base/.env" .env
        mkdir -p bootstrap/cache
    }
@endtask

@task('runComposer')
    run_composer() {
        echo 'Installing PHP dependencies.'
        "$php" /usr/local/bin/composer install --prefer-dist --no-dev --no-interaction --no-progress --no-scripts --no-plugins --optimize-autoloader
        "$php" /usr/local/bin/composer check-platform-reqs --no-dev
        "$php" artisan clear-compiled --no-interaction
        "$php" artisan package:discover --no-interaction
    }
@endtask

@task('runNpm')
    run_npm() {
        echo 'Installing frontend dependencies.'
        npm ci --no-audit --no-fund
    }
@endtask

@task('generateAssets')
    generate_assets() {
        echo 'Building frontend assets.'
        npm run build
        test -s public/build/manifest.json
    }
@endtask

@task('publishLivewireAssets')
    publish_livewire_assets() {
        "$php" artisan livewire:publish --assets --no-interaction
    }
@endtask

@task('backupDatabase')
    backup_database() {
        local database backup
        database=$(sed -n 's/^DB_DATABASE=//p' "$base/.env")
        [[ "$database" =~ ^zrenie_test(_restored_[0-9]{8})?$ ]] ||
            fail 'The configured database does not match the production database for this site.'

        echo 'Backing up the database.'
        backup="$base/backups/$version.sql.gz"
        backup_tmp="$backup.tmp"
        (umask 077; mysqldump --defaults-extra-file="$base/.mysql.cnf" --single-transaction --quick \
            --no-tablespaces --set-gtid-purged=OFF "$database" | gzip > "$backup_tmp")
        gzip -t "$backup_tmp"
        mv -T -- "$backup_tmp" "$backup"
        backup_tmp=''
    }
@endtask

@task('migrateDatabase')
    migrate_database() {
        echo 'Running database migrations.'
        "$php" artisan migrate --force --no-interaction
    }
@endtask

@task('optimizeInstallation')
    optimize_installation() {
        "$php" artisan view:clear --no-interaction
        "$php" artisan config:cache --no-interaction
        "$php" artisan event:cache --no-interaction
    }
@endtask

@task('generateSitemap')
    generate_sitemap() {
        "$php" artisan sitemap:generate --no-interaction
    }
@endtask

@task('switchRelease')
    switch_release() {
        local target=$1 http
        switching=1
        atomic_link current "$target"
        cd "$target"

        # This site has its own Redis cache database. Clearing it flushes that database.
        if grep -Fxq 'CACHE_DRIVER=redis' "$base/.env" && grep -Fxq 'REDIS_CACHE_DB=15' "$base/.env"; then
            "$php" artisan cache:clear --no-interaction
        fi

        http=$(curl --noproxy '*' --max-time 20 --resolve "$host:443:127.0.0.1" \
            -sS -o /dev/null -w '%{http_code}' "https://$host/") ||
            fail "Could not check https://$host/ after switching releases."
        [[ "$http" == 200 ]] || fail "Home page check returned HTTP $http."

        "$php" artisan queue:restart --no-interaction
        atomic_link previous "$active"
        switching=0
        printf 'Active release: %s, HTTPS %s\n' "${target##*/}" "$http"
    }
@endtask

@task('cleanOldReleases')
    clean_old_releases() {
        local name target kept=0
        local pattern='^[0-9]{8}-[0-9]{6}-[a-f0-9]{12}(-[a-zA-Z0-9]{6})?$'

        while IFS= read -r name; do
            target="$releases/$name"
            [[ "$name" =~ $pattern && "$target" != "$release" && "$target" != "$active" ]] || continue
            ready_release "$target" || continue
            if (( kept < 1 )); then
                kept=$((kept + 1))
            else
                rm -rf -- "$target" || echo "Could not remove $target." >&2
            fi
        done < <(find "$releases" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | LC_ALL=C sort -r)

        kept=0
        while IFS= read -r name; do
            [[ "${name%.sql.gz}" =~ $pattern && "$name" != "$version.sql.gz" ]] || continue
            if (( kept < 2 )); then
                kept=$((kept + 1))
            else
                rm -f -- "$base/backups/$name" || echo "Could not remove backup $name." >&2
            fi
        done < <(find "$base/backups" -maxdepth 1 -type f -name '*.sql.gz' -printf '%f\n' | LC_ALL=C sort -r)
    }
@endtask

@task('deployRelease', ['on' => 'local'])
    exec bash -se <<'ENVOY_LOCAL'
    set -euo pipefail
    @run('requireMain')
    test -r {{ $key }} || { echo 'SSH key for zrenie.clinic is missing.' >&2; exit 1; }

    sha=$(git rev-parse --verify 'refs/heads/main^{commit}')
    archive=$(mktemp "${TMPDIR:-/tmp}/zrenie-envoy.XXXXXX")
    trap 'rm -f -- "$archive"' EXIT
    git archive --format=tar -o "$archive" "$sha"

    remote_script=$(cat <<'ENVOY_REMOTE'
    @run('releaseEnvironment')
    @run('unpackSource')
    @run('updateSymlinks')
    @run('runComposer')
    @run('runNpm')
    @run('generateAssets')
    @run('publishLivewireAssets')
    @run('backupDatabase')
    @run('migrateDatabase')
    @run('optimizeInstallation')
    @run('generateSitemap')
    @run('switchRelease')
    @run('cleanOldReleases')

    initialize_release
    unpack_source "$1"
    update_symlinks
    run_composer
    run_npm
    generate_assets
    publish_livewire_assets
    backup_database
    migrate_database
    optimize_installation
    generate_sitemap
    printf '%s\n' "$1" > .release-sha
    switch_release "$release"
    clean_old_releases
    ENVOY_REMOTE
    )
    printf -v remote_command 'bash -c %q bash %q' "$remote_script" "$sha"
    {{ $ssh }} "$remote_command" < "$archive"
    ENVOY_LOCAL
@endtask

@task('rollback', ['on' => 'local'])
    test -r {{ $key }} || { echo 'SSH key for zrenie.clinic is missing.' >&2; exit 1; }
    {{ $ssh }} 'bash -se' <<'ENVOY_REMOTE'
    @run('releaseEnvironment')
    @run('optimizeInstallation')
    @run('switchRelease')

    initialize_release
    previous=$(readlink "$base/previous") || fail 'There is no previous release to roll back to.'
    [[ "$previous" != "$active" ]] && ready_release "$previous" ||
        fail "Previous release is incomplete or already active: $previous"
    printf 'Rolling back from %s to %s\n' "${active##*/}" "${previous##*/}"
    cd "$previous"
    optimize_installation
    switch_release "$previous"
    ENVOY_REMOTE
@endtask
