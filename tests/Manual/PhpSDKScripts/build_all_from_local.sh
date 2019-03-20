set -ex

for LANG in node
do
    EXECDIR=/home/vagrant/bpm-plugins/ProcessMaker/pm4-docker-executor-$LANG
    SDKDIR=/home/vagrant/processmaker/storage/api/${LANG}-sdk

    rm -rf $SDKDIR
    mkdir -p $SDKDIR
    mkdir -p $EXECDIR
    php artisan bpm:sdk $LANG $SDKDIR

    COPY_TO=pm4-sdk-$LANG
    pushd $EXECDIR
        rm -rf src/$COPY_TO
        cp -r $SDKDIR src/$COPY_TO
        ./build.sh
    popd
done