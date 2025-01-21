FROM debian
WORKDIR /var/www/html
COPY ./files/index.php /var/www/html/
COPY ./files/_mapping/training_data.csv /var/www/html/_mapping/training_data.csv
RUN apt update && apt install php -y && mkdir /var/www/html/outputs && chmod 777 -R /var/www/html
USER 1000
EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80"]