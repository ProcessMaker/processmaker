set -ex

for LANG in lua php node
do
    EXECDIR=/home/vagrant/bpm-plugins/ProcessMaker/pm4-docker-executor-$LANG
    SDKDIR=/home/vagrant/processmaker/storage/api/${LANG}-sdk

    mkdir -p $SDKDIR
    mkdir -p $EXECDIR
    php artisan bpm:sdk $LANG $SDKDIR

    pushd $EXECDIR
        if [ "$LANG" == "php" ]; then
            rm -rf src/php-sdk
            mv $SDKDIR src
        elif [ "$LANG" == "node" ]; then
            rm -rf node-sdk
            mv $SDKDIR .
        elif [ "$LANG" == "lua" ]; then
            rm -rf lua-sdk
            mv $SDKDIR .
        else
            echo "NONE"
        fi
        ./build.sh
    popd
done