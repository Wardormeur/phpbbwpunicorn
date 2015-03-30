#!/usr/bin/env sh
SRC_DIR="`pwd`"
cd "`dirname "$0"`"
cd "../phpbb/epv/src"
BIN_TARGET="`pwd`/EPV.php"
cd "$SRC_DIR"
"$BIN_TARGET" "$@"
