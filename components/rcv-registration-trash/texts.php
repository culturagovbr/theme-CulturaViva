<?php

use MapasCulturais\i;

return [
    // explicações e dicas
    'explicacaoEnvio' => i::__('Cole os números das inscrições do Cadastro Nacional. Antes de enviar, o sistema mostra o que acontece com cada uma.'),
    'explicacaoLixeira' => i::__('Inscrições na lixeira saem das filas dos avaliadores e das listas. As enviadas por esta ferramenta podem ser restauradas.'),
    'dicaNumeros' => i::__('Separe por ponto e vírgula, vírgula, espaço ou quebra de linha. O prefixo on- é opcional. Até 200 por vez.'),
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
    'selecionarTodas' => i::__('Selecionar todas'),
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
