
TARGET_DIR=build
OUTPUT_FILE="phpDbManager.php"
SRC_DIR="src"
PREFIX_DIR="PDM"

TEMP_FILE="_build_temp.php"

.PHONY: all

define apppend_file
	cat "$(SRC_DIR)/$(PREFIX_DIR)/$1.php" | sed -e "s/^<?php//" >> $(TEMP_FILE)
endef

all: main

	mkdir -p $(TARGET_DIR)

	cp $(TEMP_FILE) $(TARGET_DIR)/pdm.php
	chmod u+x $(TARGET_DIR)/pdm.php
	rm $(TEMP_FILE)

main: prepare Dispatcher RevisionController DbController ProjectController Exception
	$(call apppend_file,main)

ControllerInterface:
	$(call apppend_file,ControllerInterface)

AbstractController: ControllerInterface RevisionManager Utils
	$(call apppend_file,AbstractController)

ProjectController: AbstractController
	$(call apppend_file,ProjectController)

DbController: AbstractController
	$(call apppend_file,DbController)

RevisionController: AbstractController
	$(call apppend_file,RevisionController)

Dispatcher: AbstractController
	$(call apppend_file,Dispatcher)

Exception:
	$(call apppend_file,Exception)

Revision: RevisionInfo
	$(call apppend_file,Revision)

RevisionInfo:
	$(call apppend_file,RevisionInfo)

RevisionManager: Revision
	$(call apppend_file,RevisionManager)

Utils:
	$(call apppend_file,Utils)



prepare:
	echo "#!/usr/bin/php" > $(TEMP_FILE)
	echo "<?php" >> $(TEMP_FILE)
