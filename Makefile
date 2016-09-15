
TARGET_DIR=build
OUTPUT_FILE="phpDbManager.php"

.PHONY: all

prepare:
	TMP_FILE=$(shell mktemp _buildXXXX.php)
	echo $(TMP_FILE)

main: prepare
	echo "preprare"

all: main
	$(shell rm $$TMP_FILE)
