#!/bin/sh
if git rev-parse --verify HEAD >/dev/null 2>&1
then
    against=HEAD
else
    # Initial commit: diff against an empty tree object
    against=4b825dc642cb6eb9a060e54bf8d69288fbee4904
fi
# Redirect output to stderr.
exec 1>&2

IS_ERROR=0
# コミットされるファイルのうち、.phpで終わるもの
for FILE in `git diff-index --name-status $against -- | grep -E '^[AUM].*\.php$'| cut -c3-`; do
    # シンタックスのチェック
    if php -l $FILE; then
        # PSR準拠でコード書き換え
        vendor/bin/php-cs-fixer fix $FILE
        git add $FILE

        # PHPMDで未使用変数などのチェック
        if ! vendor/bin/phpmd $FILE text unusedcode,codesize,naming; then
            IS_ERROR=1
        fi
    else
        IS_ERROR=1
    fi
done
exit $IS_ERROR
