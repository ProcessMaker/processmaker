set -ex

for LANG in lua php node
do
    EXECDIR=/home/vagrant/bpm-plugins/ProcessMaker/pm4-docker-executor-$LANG
    SDKDIR=/home/vagrant/processmaker/storage/api/${LANG}-sdk

    rm -rf $SDKDIR
    mkdir -p $SDKDIR
    mkdir -p $EXECDIR
    php artisan bpm:sdk $LANG $SDKDIR

    COPY_TO=pm4-sdk-$LANG
    pushd $EXECDIR
        if [ "$LANG" == "php" ]; then
            echo "GOT PWD ${PWD}"
            rm -rf src/$COPY_TO
            cp -r $SDKDIR src/$COPY_TO
        elif [ "$LANG" == "node" ]; then
            rm -rf $COPY_TO
            cp -r $SDKDIR $COPY_TO
        elif [ "$LANG" == "lua" ]; then
            rm -rf $COPY_TO
            cp -r $SDKDIR $COPY_TO
        else
            echo "NONE"
        fi
        ./build.sh
    popd
done