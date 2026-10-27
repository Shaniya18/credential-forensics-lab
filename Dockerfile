# Official PHP image with Apache already configured to serve PHP from /var/www/html
FROM php:8.3-apache

# Copy the application files into Apache's web root
COPY . /var/www/html/

# Tests, docs and Git/Docker helper files are not needed inside the running container
RUN cd /var/www/html \
    && rm -rf tests Dockerfile .dockerignore .git git_workflow.sh git_workflow.ps1 README.md requirements.txt \
    && chown -R www-data:www-data /var/www/html

# Apache listens on port 80 inside the container
EXPOSE 80

# The base image already starts Apache (apache2-foreground), so no CMD is needed.
