FROM debian
WORKDIR /var/www/html
COPY ./index.php /var/www/html/
RUN apt update && apt install php -y && chmod 777 -R /var/www/html
USER 1000
EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80"]
