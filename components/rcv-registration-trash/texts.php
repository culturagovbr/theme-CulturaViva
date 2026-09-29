<?php

use MapasCulturais\i;

return [
    // situações da análise
    'bloqueada' => i::__('Bloqueada'),
    'aviso' => i::__('Com aviso'),
    'liberada' => i::__('Liberada'),

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

    // resultados
    'enviada' => i::__('Enviada para a lixeira'),
    'restaurada' => i::__('Restaurada'),
    'ignorada' => i::__('Não processada'),

    'erro' => i::__('Não foi possível concluir a operação.'),
    'enviadas' => i::__('%s inscrições enviadas para a lixeira.'),
    'restauradas' => i::__('%s inscrições restauradas.'),
];
