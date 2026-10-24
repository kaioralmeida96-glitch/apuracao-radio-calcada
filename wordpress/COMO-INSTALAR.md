# Como instalar a apuração no WordPress

A aplicação completa vai como um **plugin**. Não é preciso colar HTML nem mexer no tema.

Arquivo para instalar: `dist/apuracao-radio-calcada.zip`

## 1. Instalar o plugin

1. Entre no painel do WordPress (`seusite.com.br/wp-admin`).
2. Vá em **Plugins → Adicionar novo plugin**.
3. Clique em **Enviar plugin**, no topo da tela.
4. Clique em **Escolher arquivo**, selecione `apuracao-radio-calcada.zip` e clique em **Instalar agora**.
5. Clique em **Ativar plugin**.

## 2. A página da apuração já está no ar

Depois de ativar, a apuração completa (2º turno, Governadores, 1º turno e Mapa) abre em:

**`seusite.com.br/apuracao/`**

O link aparece em **Configurações → Apuração 2026**. Lá também dá para trocar o endereço (por exemplo, para `eleicoes-2026`).

Essa página ocupa a tela toda, com o visual da apuração, sem o cabeçalho do tema.

## 3. Usar a apuração como página inicial (opcional)

Para que quem abrir `seusite.com.br` caia direto na apuração, sem precisar do `/apuracao/`:

1. Vá em **Configurações → Apuração 2026**.
2. Marque **Usar a apuração como página inicial do site**.
3. Clique em **Salvar configurações**.

Só a página inicial muda. As outras páginas, os posts, a busca e o painel continuam iguais, e `/apuracao/` também continua funcionando. Para voltar à página inicial de antes (por exemplo, depois da eleição), é só desmarcar e salvar.

## 4. Anúncios e QR code

Em **Configurações → Apuração 2026**, nas partes **Anúncios** e **QR code**:

| Espaço | Onde aparece | Banner computador | Banner celular |
|---|---|---|---|
| Topo | Abaixo do menu de abas | 970 × 90 | 320 × 100 |
| Meio | Entre as seções da aba aberta | 970 × 250 | 300 × 250 |
| Rodapé | Antes do rodapé | 970 × 90 | 320 × 100 |
| QR code | Ao lado do anúncio do topo (no celular, abaixo dele) | quadrado, 300 × 300 | o mesmo |

1. Em cada campo de imagem, clique em **Escolher imagem** e envie ou escolha o banner na biblioteca de mídia.
2. Preencha o **link** para onde o anúncio leva ao clicar (opcional).
3. Clique em **Salvar configurações**. O site passa a mostrar o anúncio na próxima vez que a página for aberta.

- Mandou só uma imagem (só computador ou só celular)? Ela é usada nos dois.
- Espaço sem imagem não aparece no site. Para tirar um anúncio, clique em **Remover** e salve.
- Para ver onde ficam os espaços vazios, use o link **Ver onde ficam os espaços**, na mesma tela.

## 5. Colocar a apuração dentro de uma página ou post (opcional)

Para mostrar a apuração dentro do layout do site, com cabeçalho, menu e rodapé do tema:

1. Crie ou edite uma página (ex.: "Eleições 2026").
2. Adicione um bloco **Shortcode**. No editor clássico, basta colar no texto.
3. Cole um destes códigos:

| Código | O que mostra |
|---|---|
| `[apuracao_2026]` | 2º turno ao vivo (presidente, mapa e governadores) |
| `[apuracao_2026 aba="governadores"]` | Governadores do 2º turno |
| `[apuracao_2026 aba="1turno"]` | Resultado do 1º turno |
| `[apuracao_2026 aba="mapa"]` | Mapa da apuração (1º e 2º turno) |
| `[apuracao_2026 uf="sp"]` | 2º turno já aberto em um estado |
| `[apuracao_2026 simulado="1"]` | Teste com dados fictícios do simulado do TSE |

4. Publique. O menu de abas funciona dentro da página e a altura se ajusta sozinha.

Dica: use um modelo de página "largura total" ou "sem barra lateral", se o tema tiver, para o mapa ficar maior.

## Se algo não funcionar

- **`/apuracao/` dá erro 404:** vá em **Configurações → Links permanentes** e clique em **Salvar alterações**, sem mudar nada. Isso atualiza os endereços do WordPress.
- **Usa links permanentes "Simples":** o endereço vira `seusite.com.br/?rc_apuracao=1` e funciona do mesmo jeito. O link certo aparece em Configurações → Apuração 2026.
- **Plugin de cache (WP Rocket, LiteSpeed, W3 Total Cache etc.):** pode deixar ativo. Os números vêm direto do TSE pelo navegador de quem visita, e o cache não congela a apuração.
- **Números zerados antes de domingo:** é normal. O TSE só publica o 2º turno depois que as urnas fecham. Para testar antes, use `simulado="1"`.

## Atualizar o plugin depois de uma mudança

1. Gere o zip novo: `powershell -ExecutionPolicy Bypass -File wordpress\build.ps1`
2. No WordPress: **Plugins → Adicionar novo plugin → Enviar plugin**, envie o zip novo e escolha **Substituir o atual pelo enviado**.
