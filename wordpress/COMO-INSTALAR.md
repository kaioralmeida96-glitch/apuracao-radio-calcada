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

## 3. Colocar a apuração dentro de uma página ou post (opcional)

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
