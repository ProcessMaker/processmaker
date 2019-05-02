#! /bin/bash
# Composer and Node.js is required,
# this script was proved on Centos7

# Variables
SCRIPT=$(readlink -f $0);
dir_base=`dirname $SCRIPT`;


# Check if is the path directory right.
if [ ! -f  package.json ]; then
                echo "\nError, the script has that be execute within build directory\n";
                exit;
fi
# Check if the field is empty
if [ -z "$1" ]; then
                read -r -p "What's the name for the file?? -> " cn_pm
                cn_pm=${cn_pm:-spark-build}
                param1=$cn_pm ;
        else
                param1=$1 ;

fi

# Deploy PM Spark
composer install --no-dev ;
npm install ;
export NODE_OPTIONS="--max-old-space-size=4096" ;
npm run prod ;

echo "\n\n\n\nDone deploy.\n\n\n\n"

# Remove unnecessary files
cp -p -v -r $dir_base $dir_base/../$param1 ;
rm -rf $dir_base/../$param1/node_modules/ ;
rm -rf $dir_base/../$param1/build_script.sh $dir_base/../$param1/.gitignore $dir_base/../$param1/.gitattributes ;
rm -rf  $dir_base/../$param1/.git $dir_base/../$param1/.gitbook $dir_base/../$param1/.circleci ;

# Compress the files
tar -czvf $param1.tar.gz $dir_base/../$param1/ && rm -rf $dir_base/../$param1 && echo "\n\nSucessfull, the file $dir_base/$param1.tar.gz was created.\n\n" ;
