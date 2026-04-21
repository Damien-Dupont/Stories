FROM alpine:latest
LABEL Name=stories Version=0.0.1
RUN apk add --no-cache fortune
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENTRYPOINT ["sh", "-c", "fortune -a | cat"]
