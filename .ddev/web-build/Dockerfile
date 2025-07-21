ARG BASE_IMAGE
FROM $BASE_IMAGE

# 1. Actualizar sistema e instalar dependencias
RUN apt-get update && \
    apt-get install -y ca-certificates curl openssl && \
    rm -rf /var/lib/apt/lists/*

# 2. Descargar y configurar todos los certificados necesarios
RUN mkdir -p /tmp/certs && \
    curl -o /tmp/certs/root2.crt https://www.argentina.gob.ar/sites/default/files/root2.crt && \
    curl -o /tmp/certs/intermediate.crt https://acraiz.gov.ar/Content/Archivos/certificados/ACRAIZ.crt && \
    cat /tmp/certs/root2.crt /tmp/certs/intermediate.crt > /usr/local/share/ca-certificates/bcra-chain.crt && \
    chmod 644 /usr/local/share/ca-certificates/bcra-chain.crt && \
    update-ca-certificates --fresh && \
    rm -rf /tmp/certs

# 3. Configurar variables de entorno SSL
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt
ENV CURL_CA_BUNDLE=/etc/ssl/certs/ca-certificates.crt