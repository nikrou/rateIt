DIST=.dist
PLUGIN_NAME=$(shell basename `pwd`)
SOURCE=./*
TARGET=../target

config: clean manifest
	mkdir -p $(DIST)/$(PLUGIN_NAME)
	cp -pr _*.php BUGS CHANGELOG.md *.css default-templates *.png inc index.php \
	js locales MANIFEST README.md LICENSE $(DIST)/$(PLUGIN_NAME)/
	find $(DIST) -name '*~' -exec rm \{\} \;

dist: config
	cd $(DIST); \
	mkdir -p $(TARGET); \
	zip -v -r9 $(TARGET)/plugin-$(PLUGIN_NAME)-$$(grep '/* Version' $(PLUGIN_NAME)/_define.php| cut -d"'" -f2).zip $(PLUGIN_NAME); \
	cd ..

manifest:
	@find ./ -type f|egrep -v '(*~|.git|.gitignore|.dist|target|modele|Makefile|rsync_exclude)'|sed -e 's/\.\///' -e 's/\(.*\)/$(PLUGIN_NAME)\/&/'> ./MANIFEST

clean:
	rm -fr $(DIST)
