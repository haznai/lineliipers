default:
      just --list

run:
    DOCKER_DEFAULT_PLATFORM=linux/amd64 docker build -t lineliipers:v1 . --no-cache
    -docker stop lineliipers
    -docker rm lineliipers
    docker run \
      -v $(pwd):/var/www/html \
      --name lineliipers \
      -d lineliipers:v1
