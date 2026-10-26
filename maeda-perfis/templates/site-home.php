<?php
defined('ABSPATH') || exit;
use Maeda\Perfis\Site;
?>
<section class="nt-home-hero">
    <div class="nt-wrap nt-hero-grid">
        <div class="nt-hero-copy">
            <p class="nt-eyebrow"><span></span> Perfis e desenvolvimento humano</p>
            <h1>Clareza sobre você.<br><em>Direção para crescer.</em></h1>
            <p class="nt-lead">Seu jeito de agir. Seus interesses. Sua forma de contribuir. Conecte suas respostas a novas possibilidades de desenvolvimento.</p>
            <div class="nt-hero-actions"><?php echo Site::button('Encontrar meu teste', Site::url('shop')); ?><a class="nt-text-link" href="#relatorio">Conhecer o relatório <span>↗</span></a></div>
            <p class="nt-hero-note">Para você. Para equipes. Para comunidades.</p>
        </div>
        <div class="nt-hero-visual">
            <div class="nt-orbit nt-orbit-one"></div><div class="nt-orbit nt-orbit-two"></div>
            <span class="nt-floating-label nt-float-top"><?php echo Site::icon('spark'); ?> Descobrir é um começo.</span>
            <?php Site::report_demo(); ?>
            <span class="nt-floating-label nt-float-bottom"><?php echo Site::icon('check'); ?> Uma leitura. Novos caminhos.</span>
        </div>
    </div>
</section>
<div class="nt-subject-line"><div class="nt-wrap"><span>Um olhar para cada momento</span><p>Comportamento <i>✦</i> Liderança <i>✦</i> Carreira <i>✦</i> Serviço</p></div></div>
<section class="nt-section nt-wrap" id="modulos">
    <div class="nt-section-heading nt-heading-split"><div><p class="nt-kicker">QUATRO CAMINHOS PARA EXPLORAR</p><h2>Comece pelo que<br>faz sentido agora.</h2></div><p>Escolha um objetivo. Cada módulo tem seu próprio questionário, suas dimensões e um relatório individual para aprofundar a reflexão.</p></div>
    <?php Site::module_cards(); ?>
</section>
<section class="nt-report-section" id="relatorio"><div class="nt-wrap nt-editorial-grid">
    <div class="nt-report-composition"><?php Site::report_demo(); ?><div class="nt-report-annotation"><span>01 / ENTENDER</span><p>Os números mostram o panorama.<br>O texto ajuda a fazer conexões.</p></div></div>
    <div class="nt-editorial-copy"><p class="nt-kicker">SEU PERFIL, COM CONTEXTO</p><h2>Mais do que se ver<br>em um gráfico.<br><em>É se reconhecer na leitura.</em></h2><p>Um relatório que reúne suas respostas e propõe perguntas que merecem tempo. Para compreender preferências, conversar sobre experiências e pensar em ações possíveis.</p><ul class="nt-feature-list"><li><?php echo Site::icon('check'); ?><div><h3>Um panorama visual</h3><p>Radar e barras com as médias de cada dimensão.</p></div></li><li><?php echo Site::icon('check'); ?><div><h3>Um resumo que acompanha as respostas</h3><p>Uma leitura do conjunto, com atenção a empates e diferenças entre as médias.</p></div></li><li><?php echo Site::icon('check'); ?><div><h3>Um próximo passo possível</h3><p>Pontos de atenção, perguntas e sugestões de experiências para o cotidiano.</p></div></li></ul><p class="nt-fine">Instrumentos autorais em versão piloto. As médias descrevem respostas, sem comparação populacional ou previsão de desempenho.</p></div>
</div></section>
<section class="nt-section nt-wrap" id="para-quem"><div class="nt-section-heading"><p class="nt-kicker">DESENVOLVIMENTO ACONTECE COM PESSOAS</p><h2>Descobertas individuais.<br>Conversas que aproximam.</h2></div><div class="nt-audience-grid">
    <article><span>01 / PARA VOCÊ</span><h3>Uma pausa para olhar para si.</h3><p>Organize percepções sobre seu jeito de agir e seus interesses. Encontre perguntas para conversar sobre a fase que você está vivendo.</p></article>
    <article><span>02 / PARA EQUIPES</span><h3>Mais clareza para construir juntos.</h3><p>Distribua aplicações por módulo e use os relatórios para apoiar conversas de desenvolvimento, comunicação e colaboração.</p></article>
    <article><span>03 / PARA CARREIRAS</span><h3>Curiosidade que vira exploração.</h3><p>Conheça interesses por diferentes atividades e transforme possibilidades em experiências de aprendizagem e pesquisa profissional.</p></article>
    <article><span>04 / PARA COMUNIDADES</span><h3>Novas formas de participar.</h3><p>Explore interesses em serviço e voluntariado. Converse sobre disponibilidade, preparo e oportunidades de contribuição na comunidade.</p></article>
</div></section>
<section class="nt-process-section" id="como-funciona"><div class="nt-wrap"><div class="nt-section-heading nt-heading-split"><div><p class="nt-kicker">SIMPLES PARA COMEÇAR</p><h2>Do primeiro olhar<br>ao próximo passo.</h2></div><p>Compre para si ou escolha um pacote de um módulo para convidar outras pessoas. Tudo fica organizado na sua conta.</p></div><?php Site::steps(); ?></div></section>
<section class="nt-section nt-wrap nt-about-section" id="sobre"><div class="nt-about-symbol" aria-hidden="true"><?php echo Site::icon(); ?><span>NORTE + LUME</span></div><div><p class="nt-kicker">UM NOME. UMA PROPOSTA.</p><h2>Encontrar direção começa<br>por enxergar com mais clareza.</h2><p>Nortume nasce do encontro entre <strong>norte</strong> e <strong>lume</strong>: direção e luz. Uma marca criada para apoiar a compreensão de características e interesses, abrindo espaço para escolhas e desenvolvimento.</p><p>O relatório é um começo de conversa. O sentido se constrói quando você conecta a leitura à sua história, às pessoas e às experiências que deseja viver.</p></div></section>
<section class="nt-section nt-wrap nt-faq-section" id="perguntas"><div class="nt-section-heading"><p class="nt-kicker">ANTES DE COMEÇAR</p><h2>Boas perguntas<br>merecem clareza.</h2></div><?php Site::faq(); ?></section>
<section class="nt-wrap nt-final-cta"><div><p class="nt-kicker">SEU PRÓXIMO PASSO</p><h2>Comece por uma<br>nova perspectiva.</h2><p>Escolha o módulo que conversa com o seu momento.</p></div><?php echo Site::button('Explorar os testes', Site::url('shop'), 'nt-button-light'); ?></section>
