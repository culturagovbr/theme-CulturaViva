<?php

use MapasCulturais\i;

return [
    // explicações e dicas
    'explicacaoEnvio' => i::__('Cole os números das inscrições do Cadastro Nacional. Antes de enviar, o sistema mostra o que acontece com cada uma.'),
    'explicacaoLixeira' => i::__('Inscrições na lixeira saem das filas dos avaliadores e das listas. As enviadas por esta ferramenta podem ser restauradas.'),
    'dicaNumeros' => i::__('Separe por ponto e vírgula, vírgula, espaço ou quebra de linha. O prefixo on- é opcional. Até 200 por vez.'),
    'dicaSenha' => i::__('Confirme com a senha da sua conta. Contas sem senha cadastrada, como as que entram só pelo gov.br, não podem executar esta ação.'),
    'dicaMotivo' => i::__('Obrigatório, com pelo menos 10 caracteres. Fica registrado junto com quem enviou.'),
    'naoReconhecidos' => i::__('Não reconhecidos: %s'),
    'nadaParaEnviar' => i::__('Nenhuma inscrição da lista pode ser enviada para a lixeira.'),
    'lixeiraVazia' => i::__('Nenhuma inscrição na lixeira.'),

    // situações da análise
    'bloqueada' => i::__('Bloqueada'),
    'aviso' => i::__('Com aviso'),
    'liberada' => i::__('Liberada'),

    // dados de cada inscrição
    'responsavel' => i::__('Responsável: %s'),
    'statusAtual' => i::__('Status: %s'),
    'statusAnterior' => i::__('Status anterior: %s'),
    'fase' => i::__('Fase %s: %s'),
    'avaliacoesIniciadas' => i::__('%s iniciada(s)'),
    'avaliacoesConcluidas' => i::__('%s concluída(s)'),
    'avaliacoesEnviadas' => i::__('%s enviada(s)'),
    'avaliacoes' => i::__('%s avaliação(ões)'),
    'semAvaliacoes' => i::__('sem avaliações'),

    // motivos da análise e do resultado
    'nao_encontrada' => i::__('Não encontrada no Cadastro Nacional.'),
    'ja_na_lixeira' => i::__('Já está na lixeira.'),
    'unica_certificacao' => i::__('É a única certificação da organização: use o fluxo de desativação do ponto.'),
    'certificacao_duplicada' => i::__('Certificação em duplicidade: o selo permanece pela outra inscrição certificada.'),
    'certificada_sem_selo' => i::__('Certificada, mas a organização não tem o selo.'),
    'ponteiro' => i::__('A organização está vinculada a esta inscrição; o vínculo passa para outra inscrição ativa.'),
    'ponteiro_removido' => i::__('A organização está vinculada a esta inscrição e não tem outra inscrição ativa; o vínculo é removido.'),
    'fora_da_lixeira' => i::__('Não está na lixeira.'),
    'sem_backup' => i::__('Enviada para a lixeira fora desta ferramenta; restauração só pelo suporte técnico.'),
    'avaliacao_ja_existente' => i::__('Uma avaliação já existia e não foi reinserida.'),
    'ponteiro_alterado_depois' => i::__('O vínculo da organização mudou depois do envio e não foi restaurado.'),

    // envio e restauração
    'enviarBotao' => i::__('Enviar %s para a lixeira'),
    'enviarTitulo' => i::__('Enviar para a lixeira'),
    'enviarConfirmacao' => i::__('%s inscrição(ões) sairão das filas dos avaliadores e das listas. As avaliações iniciadas e concluídas ficam guardadas para a restauração.'),
    'restaurarBotao' => i::__('Restaurar %s'),
    'restaurarTitulo' => i::__('Restaurar inscrições'),
    'restaurarConfirmacao' => i::__('%s inscrição(ões) voltarão ao status anterior, com os avaliadores e as avaliações guardadas.'),
    'selecionarCarregadas' => i::__('Selecionar as carregadas'),
    'limparSelecao' => i::__('Limpar seleção'),
    'acimaDoLimite' => i::__('Selecione no máximo %s por vez.'),

    // busca, filtros e paginação da lixeira
    'buscarPlaceholder' => i::__('Número, organização, CNPJ ou motivo — ou cole uma lista de números'),
    'dicaBusca' => i::__('Para uma lista, separe os números por ponto e vírgula, vírgula, espaço ou quebra de linha, como no envio. Até 200.'),
    'foraDaLixeira' => i::__('%s dos %s números colados não estão na lixeira: %s'),
    'loteResumoLista' => i::__('%s restauráveis entre os %s números colados.'),
    'loteConfirmacaoLista' => i::__('Restaurar %s inscrição(ões) da lista colada de %s números?'),
    'filtrar' => i::__('Filtrar pela situação'),
    'filtro_todas' => i::__('Todas'),
    'filtro_restauraveis' => i::__('Restauráveis'),
    'filtro_sem_backup' => i::__('Sem backup'),
    'nadaEncontrado' => i::__('Nenhuma inscrição encontrada com a busca e o filtro atuais.'),
    'limparFiltros' => i::__('Limpar busca e filtro'),
    'exibindo' => i::__('Exibindo %s de %s'),
    'loteResumo' => i::__('%s restauráveis encontradas pela busca.'),
    'loteBotao' => i::__('Restaurar as %s encontradas'),
    'loteTitulo' => i::__('Restaurar em lote'),
    'loteConfirmacao' => i::__('Restaurar %s inscrição(ões) encontradas pela busca "%s"?'),
    'loteTeto' => i::__('Cada lote restaura no máximo %s; a busca encontrou %s. Repita para as demais.'),
    'loteRestantes' => i::__('Ainda restam %s restauráveis para esta busca.'),
    'selecionadas' => i::__('%s selecionada(s)'),
    'selecionar' => i::__('Selecionar %s'),

    // resultados
    'enviada' => i::__('Enviada para a lixeira'),
    'restaurada' => i::__('Restaurada'),
    'ignorada' => i::__('Não processada'),
    'enviadas' => i::__('%s inscrição(ões) enviada(s) para a lixeira.'),
    'restauradas' => i::__('%s inscrição(ões) restaurada(s).'),

    'erro' => i::__('Não foi possível concluir a operação.'),
];
