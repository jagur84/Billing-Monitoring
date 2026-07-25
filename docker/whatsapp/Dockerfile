FROM node:20-alpine

WORKDIR /app

RUN apk add --no-cache git python3 make g++

COPY docker/whatsapp/package.json ./
RUN npm install --omit=dev

COPY docker/whatsapp/index.js ./

RUN mkdir -p /app/auth

EXPOSE 3000
CMD ["node", "index.js"]
