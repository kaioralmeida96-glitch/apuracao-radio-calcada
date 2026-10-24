# Apuração 2026 · Rádio Calçada

Página única (`index.html`) com a apuração das Eleições 2026, lendo direto os arquivos públicos do TSE.

- **2º turno**: presidente ao vivo, mapa por estado e os 6 governadores em disputa
- **Governadores**: 2º turno ao vivo, com o estado acompanhado em destaque
- **1º turno**: resultado final de presidente (todos os candidatos) e dos 27 governadores
- **Mapa**: quem venceu em cada estado no 1º ou no 2º turno, com resumo do estado ao clicar

No ar em https://apuracao-radio-calcada.vercel.app

## Endereço com opções

- `?simulado=1` usa os dados fictícios do simulado do TSE
- `?uf=sp` abre o 2º turno já em um estado
- `?intervalo=20` muda o tempo de atualização (mínimo 15 segundos)

## Anúncios e QR code

- No WordPress: **Configurações → Apuração 2026** (veja `wordpress/COMO-INSTALAR.md`)
- Na Vercel: edite `ads.json` com os endereços das imagens (`desktop`, `mobile`, `link`) de cada espaço (`topo`, `meio`, `rodape`) e do `qr`
- `?anuncios=exemplo` mostra onde ficam os espaços vazios

## Rodar no computador

```bash
node .claude/serve.js
```

Depois abra http://localhost:8080.

## Publicar

```bash
npx vercel deploy --prod
```
