# theme-CulturaViva
Este repositório contém especificidades do Rede Cultura Viva para funcionar como tema do Mapa da Cultura.

## Configuração do tema
Há duas chaves principais de configuração do tema:

- `rcv.seals` (env: RCV_SEALS) - Ids dos selos certificadores de pontos e pontões de cultura, separados por vírgula
- `rcv.opportunityId` (env: RCV_OPPORTUNITY_ID) - Id da oportunidade do cadastro da Rede Cultura Viva

## Filtros da API
- A API retorna por padrão somente agentes do tipo coletivo, sendo possível pedir para que retorne também os agentes individuais (informando o filtro pelo tipo, ou por id, ou por usuário proprietário do agente) - _Esse filtro serve para que a lista e mapa de pontos de cultura não exibam agentes individuais e também para que consultas feitas por aplicativos terceiros vejam somente as organizações._
- A API da Rede Cultura Viva retorna somente os pontos e pontões certificados, ou seja, aqueles que possuam um ou mais dos selos certificadores de pontos e pontões de cultura configurados - _Esse filtro é para que a lista e mapa de pontos de cultura não exiba agentes individuais e também que consultas feitas por aplicativos de terceiros veja somente as organizações CERTIFICADAS._
- Para usuários com poderes administrativos na oportunidade do cadastro, a API retornará também as organizações ainda não certificadas, mas que iniciaram o processo de certificação. Essas possuem um metadado identificador (rcv_tipo) com valor 'ponto'. - _Esse filtro é para que nas páginas de gestão o sistema consiga exibir para o gestor também as organizações ainda não certificadas._
- Filtro para que a API também retorne para o usuário logado todos os agentes que este é dono ou administra. - _Para que o usuário possa escolher no momento do cadastro qual é a organização, dentre todas que ele possui cadastradas no mapas, que é a do ponto ou pontão de cultura._

## Data da certificação
A data impressa no certificado é o `createTimestamp` da relação entre a organização e o selo de verificação (`rcv.verificationSeals`), criada pelo core na aprovação da inscrição. O core não prevê editar essa data, então o tema expõe uma ação própria (`CertificationDate.php`).

- Acesso: botão "Alterar data da certificação" na tela do certificado (`seal/sealRelation/<id>`), visível só para usuários com o papel `admin`.
- Confirmação: a alteração pede a senha da conta (login local). Conta sem senha cadastrada, como a que entra só pelo gov.br, não consegue executar a ação. Senhas erradas contam nos mesmos metadados de tentativa e bloqueio do login (`AUTH_NUMBER_ATTEMPTS` e `AUTH_BLOCK_TIME`, lidos em `rcv.passwordAttempts` e `rcv.passwordBlockTime`), então o bloqueio vale para o login e para a ação.
- Regras: a data não pode ser inválida, anterior a 2004 (início da Política Nacional Cultura Viva) nem futura. A validade do selo (`validateDate`) é recalculada a partir da nova data, como o core faz na criação.
- Efeito: o certificado passa a mostrar a nova data. A coluna "Certificação" da listagem de organizações e a planilha exportada leem a primeira relação de selo da organização, que em geral é a de verificação; quando outro selo veio antes, esses dois lugares seguem mostrando a data daquele selo.
- Auditoria: cada alteração grava no `blame_log` do MapasBlame a ação `rcv-data-certificacao alterar`, com a relação, a organização, o selo, a data anterior e a nova (a senha nunca é gravada).
- Testes: `vendor/bin/phpunit src/themes/CulturaViva/tests`. O de integração roda contra o banco, numa transação desfeita no fim, com `CULTURAVIVA_INTEGRATION=1` e `HTTP_HOST` do subsite.
## Lixeira de inscrições
O core não prevê lixeira para inscrições. O tema usa o status `-10` para tirar inscrições da cadeia do Cadastro Nacional (`rcv.opportunityId` e fases filhas) de todos os fluxos, sem apagá-las.

- Acesso: tela `Painel > Administração > Lixeira de inscrições` (`panel/rcv-lixeira`), liberada só para usuários com o papel `saasSuperAdmin`.
- Confirmação: enviar e restaurar pedem a senha da conta (login local). Conta sem senha cadastrada, como a que entra só pelo gov.br, não consegue executar essas ações.
- Auditoria: além do registro de requisições do MapasBlame, cada envio e restauração grava no `blame_log` a ação `rcv-lixeira <ação>` com os números, o resultado de cada um e o motivo (a senha nunca é gravada).
- Envio: os números (`on-123`, separados por `;`, vírgula, espaço ou quebra de linha, até 200 por vez) passam por uma análise antes. Em todas as fases, a inscrição vai para `-10`, perde avaliadores (`valuers` e exceções), avaliações iniciadas e concluídas e as permissões dos avaliadores; avaliações enviadas ficam. O motivo é obrigatório.
- Bloqueios: número inexistente, inscrição já na lixeira e inscrição certificada que é a única certificação da organização (nesse caso, usar a desativação do ponto). Selos nunca são alterados.
- Vínculo da organização: se o `rcv_registration` da organização aponta para a inscrição, ele passa para outra inscrição ativa da organização (certificada do mesmo tipo, certificada de outro tipo ou a mais recente) ou é removido.
- Backup e restauração: o estado anterior fica no metadado `rcv_lixeira` de cada fase, com quem enviou, quando e o motivo. A aba "Na lixeira" restaura a partir dele. Inscrições colocadas em `-10` fora da ferramenta não têm backup.
- Proteções (`RegistrationTrash.php`): não permite avaliar inscrição na lixeira nem tirá-la do `-10` pela entidade, e remove avaliadores atribuídos a ela após cada redistribuição.
- Resumo da oportunidade: o filtro `RegistrationTrashSummaryFilter` tira o `-10` apenas da contagem de `Opportunity::getSummary`. Se a lógica do filtro mudar mantendo o nome `rcv_lixeira_resumo`, limpar o cache de consultas do Doctrine no deploy.
- Testes: `vendor/bin/phpunit src/themes/CulturaViva/tests`. Os de integração rodam contra o banco, numa transação desfeita no fim, com `CULTURAVIVA_INTEGRATION=1` e `HTTP_HOST` do subsite.
