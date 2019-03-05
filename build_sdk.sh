SWAGGER_DIR=$(mktemp -d)
BPM_DIR=${PWD}
pushd $SWAGGER_DIR
    docker pull swaggerapi/swagger-codegen-cli-v3:3.0.5
    mkdir -p out/php
    cp $BPM_DIR/storage/api-docs/api-docs.json .
    docker run --rm -v ${PWD}:/local swaggerapi/swagger-codegen-cli-v3:3.0.5 \
    generate -i /local/api-docs.json -l php -o /local/out/php

    sudo chown -R vagrant:vagrant out/php/SwaggerClient-php

    # Swagger outputs random stuff for the composer name and I cant
    # figure out how to override it in the generate command above
    # so i'm using jq to edit the json file
    pushd out/php/SwaggerClient-php
        tmp=$(mktemp)
        jq '.name= "ProcessMaker/bpm-php-sdk"' composer.json > "$tmp" && mv "$tmp" composer.json
    popd
    
    mkdir -p $BPM_DIR/storage/api
    cp -r out/php/SwaggerClient-php $BPM_DIR/storage/api
popd