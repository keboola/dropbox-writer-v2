DATADIR = ${PWD}/tmp
run:
	docker-compose run --rm -v $(DATADIR):/data dev
shell:
	docker-compose run --rm --entrypoint=/bin/sh app
build:
	docker-compose build app
