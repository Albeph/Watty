FROM alpine:latest

RUN apk add --no-cache openssh openssh-sftp-server

RUN mkdir -p /var/run/sshd

RUN ssh-keygen -A

RUN adduser -D sftpuser

RUN echo "sftpuser:password" | chpasswd

RUN mkdir -p /home/sftpuser/upload && chown sftpuser:sftpuser /home/sftpuser/upload



COPY src/main.sh /usr/local/bin/main.sh
COPY src/check_files.sh /usr/local/bin/check_files.sh

RUN chmod 777 /usr/local/bin/main.sh /usr/local/bin/check_files.sh

EXPOSE 22

ENTRYPOINT ["/usr/local/bin/main.sh"]

