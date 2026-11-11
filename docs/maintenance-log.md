# Registro de manutenção

Este arquivo é atualizado automaticamente pelo workflow
[`daily-contribution.yml`](../.github/workflows/daily-contribution.yml).
Cada linha registra uma verificação automatizada do repositório: inventário dos
arquivos versionados e integridade dos arquivos Markdown (links relativos, blocos de
código sem fechamento e presença do README). A coluna "Execução" aponta para a
execução do GitHub Actions que gerou a linha.

Para pausar: Actions > "Rotina diária de manutenção" > Disable workflow, ou crie a
variável de repositório `DAILY_CONTRIBUTION_ENABLED` com o valor `false`.

| Data (UTC) | Arquivos versionados | Arquivos Markdown | Links relativos quebrados | Blocos de código abertos | README | Resultado | Execução |
| --- | ---: | ---: | ---: | ---: | --- | --- | --- |
| 2026-11-11 | 28 | 1 | 0 | 0 | ok (143 linhas) | ✅ sem pendências | [#1](https://github.com/Willian829/learnquest/actions/runs/43021258721) |
