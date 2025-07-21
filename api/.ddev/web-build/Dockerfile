ARG BASE_IMAGE
FROM $BASE_IMAGE

# Instalar dependencias
RUN apt-get update && \
    apt-get install -y ca-certificates curl && \
    rm -rf /var/lib/apt/lists/*

# Copiar certificado específico
COPY certs/bcra-cert.pem /usr/local/share/ca-certificates/bcra-cert.crt
RUN chmod 644 /usr/local/share/ca-certificates/bcra-cert.crt && \
    update-ca-certificates

# Opcional: Configurar curl para usar los nuevos certs
RUN echo "cacert = /etc/ssl/certs/ca-certificates.crt" > /etc/.curlrc
