@servers(['dev' => 'deployer@67.43.226.230 -p 2243', 'production' => 'deployer@67.43.226.230 -p 2243'])

@setup
    $machine = isset($machine) ? $machine : 'production';
    $service = isset($service) ? $service : 'e-user-api.prod';
    $branch = isset($branch) ? $branch : 'production';
    $env_dir = '/www/html/e-user-api/'. $branch;
    $app_dir =  $env_dir . '/e-user-api';
    $releases_dir = $app_dir . '/releases';
    $release = date('YmdHis');
    $new_release_dir = $releases_dir . '/' . $release;
@endsetup

@story('deploy')
    clone_repository
    build_container
    scale_container
    purge-old-releases
@endstory

@task('clone_repository', ['on' => $machine])
    echo 'Cloning repository'
    [ -d {{ $new_release_dir }} ] || mkdir -p {{ $new_release_dir }}
    tar -zxf /www/html/e-user-api/{{ $branch }}/tmp/e-user-api.languelite.com.tar.gz --directory {{ $new_release_dir }}
    mv /www/html/e-user-api/{{ $branch }}/tmp/e-user-api.languelite.com.tar.gz {{ $new_release_dir }}/CICD/app
@endtask

@task('build_container', ['on' => $machine])
    echo 'Build docker container'
    cp {{ $app_dir }}/e-user-api.env {{ $new_release_dir }}/CICD
    ln -nfs {{ $new_release_dir }}/CICD/docker-compose-{{ $branch }}.yml {{ $env_dir }}/docker-compose.yml
    cd {{ $new_release_dir }}/CICD && docker build -t djed/{{ $branch }}.e-user-api:{{ $release }} .
    TAG={{ $release }} envsubst '${TAG}' < {{ $env_dir }}/docker-compose.env > {{ $env_dir }}/.env
    rm {{ $new_release_dir }}/CICD/app/e-user-api.languelite.com.tar.gz
@endtask

@task('scale_container', ['on' => $machine])
    echo 'Scale up to 2 containers'
    cd {{ $env_dir }} && docker-compose up -d --scale {{ $service }}=2 --no-recreate
    echo 'Scale down to single container'
    cd {{ $env_dir }} && docker-compose up -d --scale {{ $service }}=1 --no-recreate
    echo 'Remove old image'
    docker rmi $(docker images | grep "{{ $branch }}.e-user-api " | tail -n +2 | awk '{print $3}')
@endtask

@task('purge-old-releases', ['on' => $machine])
    cd  {{ $releases_dir }} && ls -t -r | tail -n +3 | xargs rm -rf
@endtask
