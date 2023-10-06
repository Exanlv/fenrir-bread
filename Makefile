build:
	docker build -t bread .

build-run:
	docker rm --force Breadbot; \
	docker run --env-file .env --name Breadbot -d bread
