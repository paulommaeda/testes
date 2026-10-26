/* Nortume — interface local, sem bibliotecas ou serviços de gráficos externos. */
(() => {
  'use strict';
  const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const color = value => /^#[0-9a-f]{6}$/i.test(String(value)) ? value : '#7462df';
  const number = value => Number(value || 0).toLocaleString('pt-BR');
  const mean = value => Number(value || 0).toLocaleString('pt-BR', {minimumFractionDigits:1,maximumFractionDigits:1});
  const date = value => { if (!value) return ''; const d = new Date(value.replace(' ','T') + 'Z'); return Number.isNaN(d.valueOf()) ? '' : d.toLocaleDateString('pt-BR'); };
  const paths = {
    north:'m12 2 8 19-8-5-8 5 8-19Zm0 0v14',
    receipt:'M5 3h14v18l-3-2-4 2-4-2-3 2V3Zm3 5h8M8 12h8M8 16h4',
    grid:'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
    compass:'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm4 5-2 6-6 2 2-6 6-2Z',
    users:'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM17 4a4 4 0 0 1 0 8M22 21v-2a4 4 0 0 0-3-3.9',
    sparkles:'m12 3 2.7 6.3L21 12l-6.3 2.7L12 21l-2.7-6.3L3 12l6.3-2.7L12 3ZM20 2v4M18 4h4',
    heart:'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z',
    chart:'M4 3v17h17M8 15v-4M13 15V7M18 15V4',
    box:'m12 3 9 5-9 5-9-5 9-5ZM3 8v9l9 5 9-5V8M12 13v9M7.5 5.5l9 5',
    arrow:'M5 12h14M13 6l6 6-6 6', back:'M19 12H5M11 6l-6 6 6 6',
    clock:'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18ZM12 7v5l3 2',
    file:'M14 3H5v18h14V8l-5-5ZM14 3v5h5M8 12h8M8 16h6',
    check:'m5 12 4 4L19 6', mail:'M3 5h18v14H3V5Zm0 0 9 8 9-8',
    lock:'M6 10h12v11H6V10Zm2 0V7a4 4 0 0 1 8 0v3M12 14v3',
    user:'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21v-2a6 6 0 0 1 6-5h4a6 6 0 0 1 6 5v2',
    print:'M7 8V3h10v5M7 17H3V8h18v9h-4M7 14h10v7H7V14ZM17 11h1',
    logout:'M9 4H4v16h5M10 12h11M16 7l5 5-5 5',
    plus:'M12 5v14M5 12h14', shield:'M12 3 3 7v5c0 5 9 9 9 9s9-4 9-9V7l-9-4Zm-4 9 3 3 5-6',
  };
  const icon = name => `<svg class="pmp-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="${paths[name] || paths.compass}"/></svg>`;
  const badge = (text, type='') => `<span class="pmp-pill ${type ? 'pmp-pill-'+type : ''}">${esc(text)}</span>`;
  const colors = ['#8b76dd','#6fa7b5','#8ab89d','#d7b17d','#aa94bd','#8c9fbf'];
  const labels = {assigned:'Disponível',in_progress:'Em andamento',completed:'Concluído',reserved:'Convite pendente',pending:'Pendente',accepted:'Aceito',expired:'Expirado',cancelled:'Cancelado',revoked:'Revogado'};
  const statusBadge = status => badge(labels[status] || status, ({completed:'green',in_progress:'blue',assigned:'',accepted:'green',pending:'amber',expired:'gray',cancelled:'gray',revoked:'red'})[status] || 'gray');
  const initials = name => String(name || 'Você').trim().split(/\s+/).slice(0,2).map(x=>x.charAt(0)).join('').toUpperCase();
  const btn = (label, action, extra='', id='') => `<button type="button" class="pmp-btn ${extra}" data-action="${esc(action)}" ${id ? `data-id="${esc(id)}"` : ''}>${esc(label)}</button>`;
  const empty = (title, text, button='') => `<div class="pmp-card pmp-empty"><div class="pmp-module-icon">${icon('compass')}</div><h3>${esc(title)}</h3><p>${esc(text)}</p>${button}</div>`;

  function radar(dimensions) {
    const n=dimensions.length, cx=210, cy=169, radius=110;
    const point=(i,r) => [cx+Math.cos(-Math.PI/2+i*2*Math.PI/n)*r,cy+Math.sin(-Math.PI/2+i*2*Math.PI/n)*r];
    const pts=(factor) => dimensions.map((d,i)=>point(i,radius*factor).map(v=>v.toFixed(2)).join(',')).join(' ');
    const grid=[1,2,3,4,5].map(v=>`<polygon points="${pts(v/5)}" fill="none" stroke="#eae7f2" stroke-width="1"/>`).join('');
    const axes=dimensions.map((d,i)=>{const p=point(i,radius);return `<line x1="${cx}" y1="${cy}" x2="${p[0]}" y2="${p[1]}" stroke="#eae7f2"/>`;}).join('');
    const polygon=dimensions.map((d,i)=>point(i,radius*Math.max(1,Math.min(5,Number(d.mean)))/5).map(v=>v.toFixed(2)).join(',')).join(' ');
    const dots=dimensions.map((d,i)=>{const p=point(i,radius*Math.max(1,Math.min(5,Number(d.mean)))/5);return `<circle cx="${p[0]}" cy="${p[1]}" r="3.5" fill="#8a74dc" stroke="white" stroke-width="1.5"/>`;}).join('');
    const ticks=[1,2,3,4,5].map(v=>`<text x="${cx+5}" y="${cy-radius*v/5+3}" class="pmp-radar-number">${v}</text>`).join('');
    const text=dimensions.map((d,i)=>{const p=point(i,radius+31),anchor=p[0]<cx-20?'end':p[0]>cx+20?'start':'middle';return `<text x="${p[0]}" y="${p[1]+4}" text-anchor="${anchor}" class="pmp-radar-label">${esc(d.short_label || d.label)}</text>`;}).join('');
    return `<svg class="pmp-radar" viewBox="0 0 420 340" role="img" aria-label="Radar das médias por dimensão. Os valores também estão descritos nas barras e nos textos deste relatório.">${grid}${axes}<polygon points="${polygon}" fill="#a08be5" fill-opacity=".16" stroke="#8a74dc" stroke-width="2.2" stroke-linejoin="round"/>${dots}${ticks}${text}</svg>`;
  }

  class App {
    constructor(root, config) {
      this.root=root;this.cfg=config;this.authHTML=root.querySelector('.pmp-auth-template')?.innerHTML || '';this.order=null;this.ordersList=null;this.accountSettings=null;this.data=null;this.view=config.view;this.attempt=null;this.report=null;this.index=0;this.review=false;this.dirty=false;this.savePromise=null;this.saveTimer=null;this.saveError=null;this.teamFilter='all';this.busy=new Set();
      root.addEventListener('click',event=>this.click(event));
      root.addEventListener('change',event=>this.change(event));
      root.addEventListener('submit',event=>this.submit(event));
      window.addEventListener('beforeunload',event=>{if(this.dirty || this.savePromise){event.preventDefault();event.returnValue='';}});
      window.addEventListener('popstate',()=>this.route().catch(e=>this.toast(e.message)));
    }
    module(key) {return (this.data?.modules || this.cfg.catalog.modules || []).find(m=>m.key===key) || {key,title:key,icon:'compass',color:'#7462df',description:''};}
    total(status) {return Object.values(this.data?.balances || {}).reduce((sum,b)=>sum+Number(b[status] || 0),0);}
    async api(path, body, retried=false) {
      const base=new URL(this.cfg.api), relative=new URL(path,'https://pmp.local/');
      let url;
      if(base.searchParams.has('rest_route')) {base.searchParams.set('rest_route',base.searchParams.get('rest_route').replace(/\/$/,'')+relative.pathname);url=base;} else {url=new URL(relative.pathname.replace(/^\//,''),this.cfg.api);}
      relative.searchParams.forEach((v,k)=>url.searchParams.set(k,v));
      const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),35000);
      try {
        const response=await fetch(url.toString(),{method:body===undefined?'GET':'POST',credentials:'same-origin',cache:'no-store',headers:{'X-WP-Nonce':this.cfg.nonce,'Content-Type':'application/json'},body:body===undefined?undefined:JSON.stringify(body),signal:controller.signal});
        let result;try{result=await response.json();}catch{throw new Error('O servidor retornou uma resposta inesperada. Tente novamente.');}
        if(!response.ok){
          if(result.code==='rest_cookie_invalid_nonce'&&!retried&&this.cfg.ajax_url){
            const refreshURL=new URL(this.cfg.ajax_url);refreshURL.searchParams.set('action','pmp_refresh_nonce');
            const refresh=await fetch(refreshURL.toString(),{credentials:'same-origin',cache:'no-store'});
            const fresh=await refresh.json();
            if(refresh.ok&&fresh.success&&fresh.data?.nonce){this.cfg.nonce=fresh.data.nonce;if(fresh.data.logout_url)this.cfg.logout_url=fresh.data.logout_url;return await this.api(path,body,true);}
          }
          const error=new Error(result.message || 'Não foi possível concluir a operação.');error.code=result.code;error.status=response.status;throw error;
        }
        return result;
      }catch(error){if(error.name==='AbortError')throw new Error('O servidor demorou para responder. Seu progresso já salvo será mantido. Tente novamente.');throw error;}
      finally{clearTimeout(timer);}
    }
    async init() {
      if(this.cfg.logged_in){try{this.data=await this.api('state');}catch(error){this.renderError(error);return;}}
      try{await this.route(true);}catch(error){this.renderError(error);}
    }
    async route(initial=false) {
      const query=new URL(location.href).searchParams;
      if(this.cfg.invite_token && initial){this.view='invitation';this.render();return;}
      const reportId=Number(query.get('pmp_report') || (initial?this.cfg.report_id:0));
      const attemptId=Number(query.get('pmp_attempt') || (initial?this.cfg.attempt_id:0));
      if(this.cfg.logged_in && reportId){await this.openReport(reportId,false);return;}
      if(this.cfg.logged_in && attemptId){await this.openAttempt(attemptId,false);return;}
      const view=query.get('pmp_view') || this.cfg.view || 'dashboard';
      this.view=['dashboard','catalog','tests','packages','team','login','orders','account'].includes(view)?view:'dashboard';
      if(!this.cfg.logged_in && !['login','catalog'].includes(this.view)) this.view='login';
      if(this.cfg.logged_in){
        const orderId=Number(query.get('pmp_order') || (initial?this.cfg.order_id:0));
        if(orderId){await this.openOrder(orderId,false);return;}
        if(this.view==='orders')this.ordersList=await this.api('account/orders?page='+Math.max(1,Number(query.get('pmp_orders_page') || (initial?this.cfg.orders_page:1))));
        if(this.view==='account')this.accountSettings=await this.api('account');
      }
      this.render();
    }
    url(view, id=0) {
      const url=new URL(this.cfg.base_url);['pmp_view','pmp_attempt','pmp_report','pmp_invite','pmp_order','pmp_orders_page'].forEach(k=>url.searchParams.delete(k));
      if(view==='report')url.searchParams.set('pmp_report',id);else if(view==='attempt')url.searchParams.set('pmp_attempt',id);else if(view==='order'){url.searchParams.set('pmp_view','orders');url.searchParams.set('pmp_order',id);}else url.searchParams.set('pmp_view',view);
      return url;
    }
    async go(view) {
      if(this.attempt && (this.dirty || this.savePromise)) await this.flush();
      this.view=view;this.attempt=null;this.report=null;this.order=null;this.review=false;this.cfg.notices=[];
      if(this.cfg.logged_in){
        this.data=await this.api('state');
        if(view==='orders')this.ordersList=this.data.orders;
        if(view==='account')this.accountSettings=await this.api('account');
      }
      history.pushState({},'',this.url(view));this.render();this.focusMain();
    }
    focusMain(){this.root.querySelector('.pmp-main')?.scrollIntoView({block:'start',behavior:'instant'});}
    focusQuestion(){const heading=this.root.querySelector('.pmp-question');if(heading){heading.focus({preventScroll:true});heading.scrollIntoView({block:'start',behavior:'instant'});}}
    nav() {
      const items=[['dashboard','grid','Visão geral'],['orders','receipt','Meus pedidos'],['tests','file','Meus testes'],['packages','box','Pacotes e convites'],['team','chart','Resultados'],['account','user','Dados da conta'],['catalog','compass','Explorar módulos']];
      return `<nav class="pmp-nav" aria-label="Navegação da minha conta">${items.filter(x=>this.cfg.logged_in || x[0]==='catalog').map(([key,ico,label])=>`<button type="button" data-action="nav" data-id="${key}" class="${(this.view===key || key==='orders'&&this.view==='order')?'is-active':''}" ${this.view===key||key==='orders'&&this.view==='order'?'aria-current="page"':''}>${icon(ico)}<span>${label}</span>${key==='tests'&&this.data?.stats.pending?`<span class="pmp-nav-count">${number(this.data.stats.pending)}</span>`:''}</button>`).join('')}${this.cfg.logged_in?`<a class="pmp-nav-logout" href="${esc(this.cfg.logout_url)}">${icon('logout')}<span>Sair da conta</span></a>`:''}</nav>`;
    }
    shell(content) {
      const name=this.data?.user.name || '', brand=this.cfg.brand;
      return `${this.cfg.external_header?'':`<header class="pmp-header"><a class="pmp-brand" href="${esc(this.cfg.base_url)}" aria-label="${esc(brand)} início"><span class="pmp-brand-mark">${icon('north')}</span><span><span class="pmp-brand-name">${esc(brand)}</span><span class="pmp-header-subtitle">Clareza para o próximo passo</span></span></a><a class="pmp-account-link" href="${esc(this.cfg.logged_in?this.cfg.account_url:this.cfg.login_url)}"><span class="pmp-account-name">${esc(name || 'Entrar na minha conta')}</span><span class="pmp-avatar">${this.cfg.logged_in?esc(initials(name)):icon('user')}</span></a></header>`}
      <div class="pmp-layout"><aside class="pmp-sidebar">${this.nav()}<div class="pmp-sidebar-bottom">${icon('sparkles')}Cada descoberta pode orientar<br>um próximo passo.</div></aside><div class="pmp-main" id="${esc(this.root.id)}-main">${(this.cfg.notices || []).map(n=>`<div class="pmp-notice ${n.type==='error'?'pmp-error':''}" role="${n.type==='error'?'alert':'status'}">${esc(n.text)}</div>`).join('')}${content}<footer class="pmp-footer"><span>${esc(brand)} · Seu espaço de desenvolvimento</span><a href="${esc(this.cfg.account_url)}">Minha conta ${icon('arrow')}</a></footer></div></div>`;
    }
    render() {
      const views={dashboard:()=>this.dashboard(),catalog:()=>this.catalog(),tests:()=>this.tests(),packages:()=>this.packages(),team:()=>this.team(),attempt:()=>this.attemptView(),report:()=>this.reportView(),orders:()=>this.ordersView(),order:()=>this.orderView(),account:()=>this.accountView(),invitation:()=>this.invitation(),login:()=>`<div class="pmp-heading"><div><p class="pmp-eyebrow">SEU ESPAÇO PESSOAL</p><h1>Vamos começar pela sua conta.</h1><p>Acesse seus pedidos, dados da conta, testes e resultados em um só lugar.</p></div></div>`};
      this.root.innerHTML=this.shell((views[this.view] || views.dashboard)()+(!this.cfg.logged_in&&['login','invitation'].includes(this.view)?this.authHTML:''));
      this.syncHeader();
    }
    renderError(error) {this.root.innerHTML=this.shell(`<div class="pmp-notice pmp-error" role="alert">${esc(error.message)}</div>${btn('Tentar novamente','reload')}`);}
    dashboard() {
      const name=this.data?.user.first_name || 'você', stats=this.data?.stats || {};
      const pending=(this.data?.tests.items || []).filter(a=>['assigned','in_progress'].includes(a.status)&&a.credit_status!=='revoked');
      const completed=(this.data?.tests.items || []).filter(a=>a.status==='completed');
      return `<div class="pmp-heading"><div><p class="pmp-eyebrow">MINHA CONTA NORTUME</p><h1>Olá, ${esc(name)}.</h1><p>Acompanhe suas compras, atualize seus dados e continue suas descobertas.</p></div>${badge('Meu espaço')}</div>
      <section class="pmp-hero pmp-account-hero"><div><p class="pmp-eyebrow">TUDO NO SEU ESPAÇO</p><h2>Suas compras.<br>Suas próximas descobertas.</h2><p>Dos pedidos aos resultados: acompanhe cada passo da sua experiência Nortume.</p><div class="pmp-hero-account-actions">${btn('Ver meus pedidos','nav','pmp-btn-light','orders')}${btn('Editar meus dados','nav','pmp-btn-account-outline','account')}</div></div><div class="pmp-account-hero-summary"><span>${icon('receipt')}</span><strong>${number(this.data?.orders?.total || 0)}</strong><p>${Number(this.data?.orders?.total || 0)===1?'pedido na sua conta':'pedidos na sua conta'}</p></div></section>
      ${this.accountOverview()}
      <div class="pmp-stat-grid">${this.stat('Relatórios pessoais',stats.completed || 0,'check','Testes que você concluiu')}${this.stat('Para responder',stats.pending || 0,'file','Liberados na sua conta')}${this.stat('Créditos disponíveis',this.total('available'),'box','Distribuídos por módulo')}${this.stat('Convites pendentes',this.total('reserved'),'mail','Aguardando o aceite')}</div>
      ${pending.length?`<div class="pmp-section-top"><h2>Seu próximo teste</h2>${btn('Ver todos','nav','pmp-btn-secondary pmp-btn-small','tests')}</div><div class="pmp-test-list">${pending.slice(0,2).map(a=>this.testRow(a)).join('')}</div>`:''}
      <div class="pmp-section-top"><h2>Quatro formas de se conhecer melhor</h2><button type="button" class="pmp-link-button" data-action="nav" data-id="catalog">Explorar ${icon('arrow')}</button></div><div class="pmp-module-grid">${(this.data?.modules || []).map(m=>this.moduleCard(m)).join('')}</div>
      ${completed.length?`<div class="pmp-section-top"><h2>Suas descobertas recentes</h2></div><div class="pmp-test-list">${completed.slice(0,2).map(a=>this.testRow(a)).join('')}</div>`:''}`;
    }
    syncHeader() {
      const name=this.data?.user?.name;
      if(!name)return;
      document.querySelectorAll('[data-nt-account-name]').forEach(node=>{node.textContent=name;});
      document.querySelectorAll('[data-nt-account-avatar]').forEach(node=>{node.textContent=initials(name).slice(0,1);});
    }
    orderStatus(order) {return badge(order.status_label,({completed:'green',processing:'blue','on-hold':'amber',pending:'amber',cancelled:'gray',refunded:'gray',failed:'red'})[order.status] || 'gray');}
    orderRow(order,compact=false) {
      const item=order.items[0], more=Math.max(0,order.items.length-1);
      return `<article class="pmp-order-row ${compact?'is-compact':''}"><span class="pmp-order-icon">${icon('receipt')}</span><div class="pmp-order-info"><button type="button" class="pmp-link-button" data-action="order" data-id="${order.id}">Pedido #${esc(order.number)}</button><p>${esc(order.date)}${item?' · '+esc(item.name)+(more?' + '+number(more):''):''}</p></div><div class="pmp-order-value"><strong>${esc(order.total)}</strong>${this.orderStatus(order)}</div>${!compact?btn('Ver pedido','order','pmp-btn-secondary pmp-btn-small',order.id):''}</article>`;
    }
    accountOverview() {
      const profile=this.data?.account || {}, orders=this.data?.orders?.items || [];
      return `<div class="pmp-account-overview"><section class="pmp-card pmp-account-orders"><div class="pmp-card-top"><div><h2>Meus pedidos</h2><p>Suas compras mais recentes.</p></div>${btn('Ver todos','nav','pmp-btn-secondary pmp-btn-small','orders')}</div>${orders.length?orders.slice(0,3).map(order=>this.orderRow(order,true)).join(''):`<div class="pmp-account-empty"><p>Você ainda não tem pedidos por aqui.</p><a class="pmp-link-button" href="${esc(this.cfg.shop_url)}">Explorar os testes ${icon('arrow')}</a></div>`}</section><section class="pmp-card pmp-account-data"><div class="pmp-card-top"><div><h2>Dados da conta</h2><p>Seu acesso e suas informações.</p></div><span class="pmp-account-data-icon">${icon('user')}</span></div><dl><div><dt>Nome</dt><dd>${esc([profile.first_name,profile.last_name].filter(Boolean).join(' ') || profile.display_name || this.data?.user?.name)}</dd></div><div><dt>E-mail de acesso</dt><dd>${esc(profile.email || this.data?.user?.email)}</dd></div>${profile.billing?.phone?`<div><dt>Telefone de faturamento</dt><dd>${esc(profile.billing.phone)}</dd></div>`:''}</dl>${btn('Editar meus dados','nav','pmp-btn-secondary','account')}</section></div>`;
    }
    ordersView() {
      const list=this.ordersList || this.data?.orders || {items:[]};
      return `<div class="pmp-heading"><div><p class="pmp-eyebrow">SUAS COMPRAS NA NORTUME</p><h1>Meus pedidos</h1><p>Veja o status, os itens e os detalhes de cada compra.</p></div>${badge(number(list.total || 0)+' pedidos')}</div>${list.items.length?`<section class="pmp-card pmp-orders-list">${list.items.map(order=>this.orderRow(order)).join('')}</section>${list.has_more?`<div class="pmp-more">${btn('Carregar mais pedidos','more-orders','pmp-btn-secondary')}</div>`:''}`:empty('Seu primeiro pedido pode começar aqui','As compras feitas com sua conta aparecerão nesta página.',`<a class="pmp-btn" href="${esc(this.cfg.shop_url)}">Conhecer os testes</a>`)}`;
    }
    async openOrder(id,push=true) {
      if(this.attempt&&(this.dirty||this.savePromise))await this.flush();
      const order=await this.api('account/orders/'+id);
      this.order=order;this.attempt=null;this.report=null;this.view='order';
      if(push)history.pushState({},'',this.url('order',id));
      this.render();if(push)this.focusMain();
    }
    orderView() {
      const order=this.order;if(!order)return '';
      const applications=order.items.reduce((n,item)=>n+Number(item.applications || 0),0);
      const available=['processing','completed'].includes(order.status);
      return `<button type="button" class="pmp-link-button pmp-back" data-action="nav" data-id="orders">${icon('back')}Meus pedidos</button><div class="pmp-heading"><div><p class="pmp-eyebrow">DETALHES DA SUA COMPRA</p><h1>Pedido #${esc(order.number)}</h1><p>Realizado em ${esc(order.date)}${order.payment_method?' · '+esc(order.payment_method):''}</p></div>${this.orderStatus(order)}</div><div class="pmp-order-detail-grid"><section class="pmp-card pmp-order-items"><h2>O que você comprou</h2>${order.items.map(item=>`<article><span class="pmp-order-icon">${icon('box')}</span><div><h3>${esc(item.name)}</h3><p>Quantidade: ${number(item.quantity)}</p>${item.module?`<p>${esc(item.module)}${item.applications?' · '+number(item.applications)+(Number(item.applications)===1?' aplicação':' aplicações')+' no item comprado':''}</p>`:''}${item.meta.map(meta=>`<p class="pmp-order-item-meta">${esc(meta.label)}: ${esc(meta.value)}</p>`).join('')}</div><strong>${esc(item.total)}</strong></article>`).join('')}${applications?`<div class="pmp-order-access"><p>${available?'Seus testes e pacotes liberados podem ser acompanhados na sua conta.':'A liberação dos testes acompanha a confirmação do pagamento e a situação do pedido.'}</p>${btn('Ver meus testes e pacotes','nav','pmp-btn-secondary','packages')}</div>`:''}</section><section class="pmp-card pmp-order-totals"><h2>Resumo do pedido</h2><dl>${order.totals.map(total=>`<div><dt>${esc(total.label)}</dt><dd>${esc(total.value)}</dd></div>`).join('')}</dl>${order.pay_url?`<a class="pmp-btn" href="${esc(order.pay_url)}">Continuar pagamento ${icon('arrow')}</a>`:''}${order.can_cancel?btn('Cancelar pedido','cancel-order','pmp-btn-secondary',order.id):''}</section></div><div class="pmp-order-contact-grid"><section class="pmp-card"><h2>Dados de faturamento</h2><address>${esc(order.billing_address || 'Endereço não informado neste pedido.')}</address><p>${esc(order.email)}</p>${order.phone?`<p>${esc(order.phone)}</p>`:''}</section>${order.shipping_address?`<section class="pmp-card"><h2>Endereço de entrega</h2><address>${esc(order.shipping_address)}</address></section>`:''}${order.notes.length?`<section class="pmp-card pmp-order-notes"><h2>Atualizações do pedido</h2>${order.notes.map(note=>`<article><span>${esc(note.date)}</span><p>${esc(note.text)}</p></article>`).join('')}</section>`:''}</div>`;
    }
    accountView() {
      const profile=this.accountSettings?.profile || this.data?.account || {}, billing=profile.billing || {}, countries=this.accountSettings?.countries || {};
      const input=(name,label,value='',extra='',scope='profile')=>`<div><label class="pmp-label" for="${this.root.id}-${scope}-${name}">${esc(label)}</label><input class="pmp-input" id="${this.root.id}-${scope}-${name}" name="${name}" value="${esc(value)}" ${extra}></div>`;
      return `<div class="pmp-heading"><div><p class="pmp-eyebrow">SEU PERFIL E SEU ACESSO</p><h1>Dados da conta</h1><p>Mantenha suas informações atualizadas e gerencie sua senha.</p></div></div><div class="pmp-account-settings"><section class="pmp-card"><h2>Informações pessoais</h2><p class="pmp-settings-intro">Estes são os dados usados no seu acesso à Nortume.</p><form data-form="profile" class="pmp-profile-form"><div class="pmp-form-fields pmp-profile-fields">${input('first_name','Nome',profile.first_name,'autocomplete="given-name" maxlength="100" required')}${input('last_name','Sobrenome',profile.last_name,'autocomplete="family-name" maxlength="100" required')}${input('display_name','Nome de exibição',profile.display_name,'maxlength="100" required')}${input('email','E-mail de acesso',profile.email,'type="email" autocomplete="email" maxlength="100" required')}</div><div class="pmp-password-section"><h3>E-mail e senha</h3><p>Para alterar o e-mail ou a senha, informe sua senha atual. Deixe a nova senha em branco para manter a que você usa.</p><div class="pmp-form-fields pmp-profile-fields">${input('current_password','Senha atual','','type="password" autocomplete="current-password" maxlength="128"')}${input('new_password','Nova senha','','type="password" autocomplete="new-password" minlength="12" maxlength="128"')}${input('confirm_password','Repita a nova senha','','type="password" autocomplete="new-password" minlength="12" maxlength="128"')}</div><p class="pmp-field-help">A nova senha deve ter pelo menos 12 caracteres.</p></div><p class="pmp-form-message" data-form-message role="status" tabindex="-1"></p><button type="submit" class="pmp-btn">Salvar dados da conta</button></form></section><section class="pmp-card"><h2>Endereço de faturamento</h2><p class="pmp-settings-intro">Use os dados que deseja preencher nas próximas compras. Os pedidos anteriores mantêm as informações registradas na compra.</p><form data-form="billing" class="pmp-billing-form"><div class="pmp-form-fields pmp-profile-fields">${input('first_name','Nome',billing.first_name || profile.first_name,'autocomplete="billing given-name" maxlength="100" required','billing')}${input('last_name','Sobrenome',billing.last_name || profile.last_name,'autocomplete="billing family-name" maxlength="100" required','billing')}${input('company','Empresa (opcional)',billing.company,'autocomplete="billing organization" maxlength="100"','billing')}${input('phone','Telefone de faturamento',billing.phone,'type="tel" autocomplete="billing tel" maxlength="50"','billing')}<div><label class="pmp-label" for="${this.root.id}-country">País</label><select class="pmp-input" name="country" id="${this.root.id}-country" autocomplete="billing country" required><option value="">Selecione</option>${Object.entries(countries).map(([key,label])=>`<option value="${esc(key)}" ${billing.country===key?'selected':''}>${esc(label)}</option>`).join('')}</select></div>${input('state','Estado / UF',billing.state,'autocomplete="billing address-level1" maxlength="100"','billing')}${input('postcode','CEP / código postal',billing.postcode,'autocomplete="billing postal-code" maxlength="20"','billing')}${input('city','Cidade',billing.city,'autocomplete="billing address-level2" maxlength="100"','billing')}${input('address_1','Rua e número',billing.address_1,'autocomplete="billing address-line1" maxlength="200"','billing')}${input('address_2','Complemento (opcional)',billing.address_2,'autocomplete="billing address-line2" maxlength="200"','billing')}${input('email','E-mail de faturamento',billing.email || profile.email,'type="email" autocomplete="billing email" maxlength="100" required','billing')}</div><p class="pmp-field-help">O e-mail de faturamento não altera seu e-mail de acesso.</p><p class="pmp-form-message" data-form-message role="status" tabindex="-1"></p><button type="submit" class="pmp-btn pmp-btn-secondary">Salvar endereço de faturamento</button></form></section></div>`;
    }

    stat(label,value,ico,note){return `<div class="pmp-card pmp-stat"><span class="pmp-stat-icon">${icon(ico)}</span><p class="pmp-stat-label">${esc(label)}</p><div class="pmp-stat-number">${number(value)}</div><p class="pmp-stat-note">${esc(note)}</p></div>`;}
    moduleCard(m) {
      const balance=Number(this.data?.balances[m.key]?.available || 0);
      return `<article class="pmp-card pmp-module" style="--accent:${color(m.color)}"><div class="pmp-module-head"><span class="pmp-module-icon">${icon(m.icon)}</span><h3>${esc(m.title)}</h3></div><p class="pmp-module-description">${esc(m.description)}</p><div class="pmp-module-meta"><span>${icon('file')}${number(m.question_count)} perguntas</span><span>${icon('clock')}${esc(m.minutes)} min</span></div><div class="pmp-module-footer"><span>${balance?number(balance)+(balance>1?' créditos disponíveis':' crédito disponível'):'Descubra este módulo'}</span><button type="button" class="pmp-link-button" data-action="${balance?'claim':'catalog-module'}" data-id="${esc(m.key)}">${balance?'Fazer meu teste':'Ver pacotes'} ${icon('arrow')}</button></div></article>`;
    }
    testRow(a,team=false) {
      const m=this.module(a.module_key), revoked=a.credit_status==='revoked';
      const available=a.report_available || a.status==='completed'&&a.is_own;
      const action=available?'report':'attempt', label=available?'Ver relatório':a.status==='in_progress'?'Continuar teste':'Iniciar teste';
      return `<article class="pmp-card pmp-test-row" style="--accent:${color(m.color)}"><span class="pmp-module-icon">${icon(m.icon)}</span><div class="pmp-test-info"><h3>${esc(team?a.participant_name:m.title)}</h3><p>${esc(team?m.title:a.buyer_name&&a.buyer_name!==this.data?.user.name?'Liberado por '+a.buyer_name:'Sua aplicação individual')} ${a.completed_at?'· '+date(a.completed_at):''}</p></div>${statusBadge(revoked&&!available?'revoked':a.status)}${available || !revoked&&!team?btn(label,action,'pmp-btn-secondary pmp-btn-small',a.id):''}</article>`;
    }
    more(kind){const list=this.data?.[kind];return list?.has_more?`<div class="pmp-more">${btn('Carregar mais','more','pmp-btn-secondary',kind)}</div>`:'';}
    tests() {
      const rows=this.data?.tests.items || [];
      return `<div class="pmp-heading"><div><p class="pmp-eyebrow">UMA DESCOBERTA DE CADA VEZ</p><h1>Meus testes</h1><p>Inicie, continue ou consulte as aplicações da sua conta.</p></div></div>${rows.length?`<div class="pmp-test-list">${rows.map(a=>this.testRow(a)).join('')}</div>${this.more('tests')}`:empty('Seu próximo teste pode começar aqui','Os testes adquiridos para você e os convites aceitos aparecem nesta página.',btn('Explorar módulos','nav','','catalog'))}${this.total('available')?`<div class="pmp-section-top"><h2>Usar meus créditos</h2></div><div class="pmp-module-grid">${(this.data?.modules || []).filter(m=>this.data.balances[m.key]?.available).map(m=>this.moduleCard(m)).join('')}</div>`:''}`;
    }
    catalog() {
      const modules=this.data?.modules || this.cfg.catalog.modules || [], products=this.data?.products || this.cfg.catalog.products || [];
      const selected=this.catalogModule || '';
      return `<div class="pmp-heading"><div><p class="pmp-eyebrow">ESCOLHA SUA PRÓXIMA DESCOBERTA</p><h1>Explore os módulos</h1><p>Um teste para cada objetivo. Para você ou para quem caminha com você.</p></div></div>${!this.cfg.logged_in?`<div class="pmp-notice">Crie sua conta para comprar aplicações, convidar pessoas e acompanhar seus resultados. <a href="${esc(this.cfg.login_url)}"><strong>Entrar ou cadastrar</strong></a></div>`:''}
      <div class="pmp-module-grid pmp-package-grid">${modules.filter(m=>!selected||m.key===selected).map(m=>{const packs=products.filter(p=>p.module===m.key);return `<article class="pmp-card" style="--accent:${color(m.color)}"><div class="pmp-module-head"><span class="pmp-module-icon">${icon(m.icon)}</span><h2>${esc(m.title)}</h2></div><p class="pmp-module-description">${esc(m.description)}</p><div class="pmp-module-meta"><span>${icon('file')}${number(m.question_count)} perguntas</span><span>${icon('clock')}${esc(m.minutes)} min</span>${badge('Versão piloto')}</div>${packs.length?`<div class="pmp-products">${packs.map(p=>`<div class="pmp-product"><p class="pmp-product-size">${number(p.size)} ${p.size===1?'aplicação':'aplicações'}</p><div class="pmp-product-price">${esc(p.price)}</div><a class="pmp-btn pmp-btn-light" href="${esc(this.cfg.logged_in?p.url:this.cfg.login_url)}">${this.cfg.logged_in?'Escolher pacote':'Criar conta'}</a></div>`).join('')}</div>`:'<p class="pmp-fineprint">Os pacotes deste módulo estão em preparação.</p>'}</article>`;}).join('')}</div>${selected?`<div class="pmp-more">${btn('Ver todos os módulos','all-modules','pmp-btn-secondary')}</div>`:''}<p class="pmp-fineprint">Cada aplicação corresponde a um módulo para uma pessoa. Os questionários autorais piloto apoiam reflexão e desenvolvimento; não apresentam diagnóstico nem determinam aptidão profissional.</p>`;
    }
    packages() {
      const modules=this.data?.modules || [], balances=this.data?.balances || {}, invites=this.data?.invites.items || [];
      const usable=modules.filter(m=>Number(balances[m.key]?.available || 0)>0);
      return `<div class="pmp-heading"><div><p class="pmp-eyebrow">DESENVOLVIMENTO COMPARTILHADO</p><h1>Pacotes e convites</h1><p>Use suas aplicações ou convide pessoas para responder.</p></div>${btn('Comprar aplicações','nav','pmp-btn-secondary pmp-btn-small','catalog')}</div>
      <div class="pmp-module-grid pmp-package-grid">${modules.filter(m=>balances[m.key]).map(m=>{const b=balances[m.key],used=Number(b.in_progress||0)+Number(b.completed||0);return `<article class="pmp-card" style="--accent:${color(m.color)}"><div class="pmp-module-head"><span class="pmp-module-icon">${icon(m.icon)}</span><h3>${esc(m.title)}</h3></div><div class="pmp-pack-counts"><div><strong>${number(b.available)}</strong><span>Disponíveis</span></div><div><strong>${number(Number(b.reserved||0)+Number(b.assigned||0))}</strong><span>Reservadas</span></div><div><strong>${number(used)}</strong><span>Iniciadas ou concluídas</span></div></div><div class="pmp-pack-actions">${b.available?btn('Fazer meu teste','claim','pmp-btn-secondary pmp-btn-small',m.key):''}<button class="pmp-link-button" type="button" data-action="catalog-module" data-id="${esc(m.key)}">Comprar mais ${icon('arrow')}</button></div></article>`;}).join('')}</div>
      ${usable.length?`<section class="pmp-card pmp-form"><div class="pmp-card-top"><div><h2>Convide uma pessoa</h2><p>O convite reserva uma aplicação do módulo selecionado.</p></div><span class="pmp-module-icon">${icon('mail')}</span></div><form data-form="invite"><div class="pmp-form-fields"><div><label class="pmp-label" for="${esc(this.root.id)}-module">Módulo</label><select class="pmp-input" name="module" id="${esc(this.root.id)}-module" required>${usable.map(m=>`<option value="${esc(m.key)}">${esc(m.title)} · ${number(balances[m.key].available)} disponíveis</option>`).join('')}</select></div><div><label class="pmp-label" for="${esc(this.root.id)}-email">E-mail do participante</label><input class="pmp-input" type="email" name="email" id="${esc(this.root.id)}-email" autocomplete="email" placeholder="nome@exemplo.com" required maxlength="254"></div></div><button type="submit" class="pmp-btn">${icon('mail')}Enviar convite individual</button><p class="pmp-fineprint">A pessoa criará sua própria conta. Antes de aceitar, verá que você terá acesso ao relatório final desta aplicação.</p></form></section>`:empty('Novas pessoas, novas descobertas','Compre aplicações de um módulo para distribuir convites.',btn('Explorar pacotes','nav','','catalog'))}
      <div class="pmp-section-top"><h2>Convites enviados</h2><span class="pmp-muted">${number(this.data?.invites.total || 0)}</span></div>${invites.length?`<div class="pmp-card">${invites.map(i=>{const m=this.module(i.module_key);return `<div class="pmp-invite-item"><span class="pmp-module-icon" style="--accent:${color(m.color)}">${icon('mail')}</span><div class="pmp-test-info"><h3 class="pmp-invite-email">${esc(i.email)}</h3><p>${esc(m.title)} · ${i.status==='pending'?'Validade até '+date(i.expires_at):'Enviado em '+date(i.created_at)}${i.mail_status==='failed'?' · Falha no envio do e-mail':''}</p></div>${statusBadge(i.status==='accepted'&&i.credit_status==='in_progress'?'in_progress':i.status==='accepted'&&i.credit_status==='completed'?'completed':i.status)}<div class="pmp-invite-actions">${i.status==='pending'?`<button type="button" data-action="resend" data-id="${i.id}">Reenviar</button>`:''}${['pending','accepted'].includes(i.status)&&['reserved','assigned'].includes(i.credit_status)?`<button type="button" data-action="cancel" data-id="${i.id}" class="is-danger">Cancelar e devolver crédito</button>`:''}</div></div>`;}).join('')}</div>${this.more('invites')}`:'<p class="pmp-muted">Seus convites aparecerão aqui.</p>'}`;
    }
    team() {
      const all=this.data?.funded.items || [], rows=all.filter(a=>this.teamFilter==='all'||a.module_key===this.teamFilter);
      return `<div class="pmp-heading"><div><p class="pmp-eyebrow">ACOMPANHE CADA PARTICIPANTE</p><h1>Resultados dos participantes</h1><p>Somente aplicações que você adquiriu aparecem neste espaço.</p></div></div><div class="pmp-stat-grid">${this.stat('Relatórios disponíveis',this.data?.stats.funded_completed || 0,'chart','Aplicações financiadas por você')}${this.stat('Convites pendentes',this.total('reserved'),'mail','Aguardando o aceite')}${this.stat('Para iniciar',this.total('assigned'),'file','Aplicações já liberadas')}${this.stat('Em andamento',this.total('in_progress'),'clock','Testes iniciados')}</div><div class="pmp-filter-row"><button type="button" data-action="filter" data-id="all" class="${this.teamFilter==='all'?'is-active':''}">Todos os módulos</button>${(this.data?.modules || []).map(m=>`<button type="button" data-action="filter" data-id="${esc(m.key)}" class="${this.teamFilter===m.key?'is-active':''}">${esc(m.title)}</button>`).join('')}</div>${rows.length?`<div class="pmp-test-list">${rows.map(a=>this.testRow(a,true)).join('')}</div>`:empty('Acompanhe as descobertas da sua equipe','Os participantes aparecem aqui depois de aceitarem os convites. Os relatórios ficam disponíveis após a conclusão.')}${this.more('funded')}`;
    }
    invitation() {
      const i=this.cfg.invitation;
      if(!i || i.error)return empty('Este convite não está disponível',i?.error || 'Confira o link recebido no e-mail.');
      const m=i.module || {},expired=['expired','cancelled','revoked'].includes(i.status);
      if(expired)return empty('Este convite não está mais disponível','Peça à pessoa responsável pela compra para enviar um novo convite.');
      if(i.status==='accepted'&&i.attempt_id)return empty('Seu convite já foi aceito','O teste está disponível na sua conta.',btn('Acessar meu teste','attempt','',i.attempt_id));
      return `<section class="pmp-card pmp-invitation-card" style="--accent:${color(m.color)}"><span class="pmp-module-icon pmp-intro-icon">${icon(m.icon || 'mail')}</span><p class="pmp-eyebrow">VOCÊ RECEBEU UM CONVITE</p><h1 class="pmp-intro-title">${esc(m.title || 'Um teste para você')}</h1><p class="pmp-intro-copy"><strong>${esc(i.buyer_name)}</strong> liberou uma aplicação para você explorar suas preferências e planejar novos passos.</p><div class="pmp-intro-details"><span>${icon('mail')}${esc(i.email_hint)}</span><span>${icon('clock')}Convite válido até ${date(i.expires_at)}</span></div><div class="pmp-notice"><strong>Quem poderá ver seu relatório?</strong><br>Ao concluir esta aplicação, você e ${esc(i.buyer_name)} poderão consultar o relatório final. Suas respostas individuais não serão exibidas ao comprador. Outros resultados da sua conta não serão compartilhados.</div>${!this.cfg.logged_in?`<p class="pmp-intro-copy">Entre ou crie uma conta com o mesmo e-mail que recebeu o convite, usando o formulário abaixo.</p><div class="pmp-intro-actions" style="margin-top:20px">${btn('Entrar ou criar minha conta','auth-scroll')}</div>`:!i.email_matches?`<div class="pmp-notice pmp-error">Sua conta usa ${esc(this.data?.user.email)}. Entre com a conta do e-mail destinatário do convite.</div><a class="pmp-btn pmp-btn-secondary" href="${esc(this.cfg.logout_url)}">Sair para trocar de conta</a>`:`<label class="pmp-checkbox"><input type="checkbox" name="pmp-sharing"><span>Estou ciente de que o relatório final desta aplicação será compartilhado com ${esc(i.buyer_name)} e aceito realizar o teste nessas condições.</span></label><button type="button" class="pmp-btn" data-action="accept" disabled>Aceitar convite e liberar teste ${icon('arrow')}</button>`}</section>`;
    }
    async openAttempt(id, push=true) {
      if(this.attempt&&(this.dirty||this.savePromise))await this.flush();
      const attempt=await this.api('attempts/'+id);
      if(attempt.status==='completed'){await this.openReport(id,push);return;}
      this.attempt=attempt;this.attempt.answers=Object.assign({},attempt.answers);this.index=Math.max(0,attempt.module.questions.findIndex(q=>!this.attempt.answers[q.id]));this.review=false;this.dirty=false;this.saveError=null;this.view='attempt';
      if(push)history.pushState({},'',this.url('attempt',id));this.render();if(push)this.focusMain();
    }
    attemptView() {
      const a=this.attempt;if(!a)return '';
      const m=a.module;
      const back=`<button type="button" class="pmp-link-button pmp-back pmp-no-print" data-action="nav" data-id="tests">${icon('back')}Meus testes</button>`;
      if(a.status==='assigned')return `<div class="pmp-quiz">${back}<section class="pmp-card pmp-quiz-card" style="--accent:${color(m.color)}"><span class="pmp-module-icon pmp-intro-icon">${icon(m.icon)}</span><p class="pmp-eyebrow">RESERVE UM MOMENTO PARA VOCÊ</p><h1 class="pmp-intro-title">${esc(m.title)}</h1><p class="pmp-intro-copy">${esc(m.description)}</p><div class="pmp-intro-details"><span>${icon('file')}${number(m.question_count)} perguntas</span><span>${icon('clock')}Cerca de ${esc(m.minutes)} minutos</span><span>${icon('lock')}Progresso salvo na sua conta</span></div><p class="pmp-intro-copy">${esc(m.instructions)}</p><div class="pmp-notice" style="margin-top:22px">Questionário autoral em versão piloto. O resultado apoia reflexão e desenvolvimento e não apresenta diagnóstico nem comprova aptidão.${a.shared?` O relatório final desta aplicação também será consultado por ${esc(a.buyer_name)}.`:''}</div><label class="pmp-checkbox"><input type="checkbox" name="pmp-ack"><span>Li as informações sobre a aplicação, a versão piloto e o acesso ao relatório, e desejo começar.</span></label><div class="pmp-intro-actions"><button type="button" class="pmp-btn" data-action="start" disabled>Começar meu teste ${icon('arrow')}</button>${!a.shared?btn('Devolver crédito ao saldo','release','pmp-btn-secondary'):''}</div></section></div>`;
      const answered=Object.keys(a.answers).length,total=m.questions.length,progress=Math.round(answered/total*100);
      const top=`<div class="pmp-quiz-heading"><h1>${esc(m.title)}</h1>${badge('Piloto')}</div><div class="pmp-quiz-topline"><span>${number(answered)} de ${number(total)} respondidas</span><span>${number(progress)}% concluído</span></div><div class="pmp-progress-track" role="progressbar" aria-label="Perguntas respondidas" aria-valuemin="0" aria-valuemax="${total}" aria-valuenow="${answered}"><div class="pmp-progress-fill" style="width:${progress}%"></div></div>`;
      if(this.review)return `<div class="pmp-quiz">${back}${top}<section class="pmp-card pmp-quiz-card"><p class="pmp-eyebrow">ANTES DE CONCLUIR</p><h2>Um último olhar nas suas respostas</h2><p class="pmp-question-hint" style="margin-top:10px">Escolha um número para revisar uma pergunta. Depois de concluir, este relatório ficará fechado para edição.</p><div class="pmp-review-grid">${m.questions.map((q,i)=>`<button type="button" data-action="question" data-id="${i}" class="${a.answers[q.id]?'is-answered':''}" aria-label="Pergunta ${i+1}, ${a.answers[q.id]?'respondida':'a responder'}">${i+1}</button>`).join('')}</div><div class="pmp-quiz-actions">${btn('Voltar ao teste','question','pmp-btn-secondary',Math.max(0,this.index))}<button type="button" class="pmp-btn" data-action="complete" ${answered!==total?'disabled':''}>Concluir e ver meu relatório ${icon('arrow')}</button></div><div class="pmp-save-indicator" data-save>${icon('check')}Suas respostas estão salvas</div></section></div>`;
      const q=m.questions[this.index];
      return `<div class="pmp-quiz">${back}${top}<section class="pmp-card pmp-quiz-card"><span class="pmp-question-number">PERGUNTA ${this.index+1} DE ${total}</span><h2 class="pmp-question" id="${esc(this.root.id)}-question" tabindex="-1">${esc(q.prompt)}</h2><p class="pmp-question-hint">Escolha a opção que melhor representa sua resposta hoje.</p><fieldset class="pmp-options" aria-labelledby="${esc(this.root.id)}-question">${Object.entries(m.scale).map(([value,label])=>`<label class="pmp-option ${a.answers[q.id]===Number(value)?'is-selected':''}"><input type="radio" name="pmp-answer" value="${esc(value)}" data-question="${esc(q.id)}" ${a.answers[q.id]===Number(value)?'checked':''}><span>${esc(label)}</span><span class="pmp-option-num" aria-hidden="true">${esc(value)}</span></label>`).join('')}</fieldset><div class="pmp-quiz-actions"><button type="button" class="pmp-btn pmp-btn-secondary" data-action="prev" ${this.index===0?'disabled':''}>${icon('back')}Anterior</button><button type="button" class="pmp-btn" data-action="next" ${a.answers[q.id]?'':'disabled'}>${this.index===total-1?'Revisar e concluir':'Próxima'}${icon('arrow')}</button></div><div class="pmp-save-indicator" data-save>${icon('check')}Progresso salvo na sua conta</div></section><div style="text-align:center;margin-top:8px"><button type="button" class="pmp-link-button" data-action="review">Revisar perguntas</button></div></div>`;
    }
    async openReport(id,push=true) {
      if(this.attempt&&(this.dirty||this.savePromise))await this.flush();
      this.report=await this.api('reports/'+id);this.attempt=null;this.view='report';
      if(push)history.pushState({},'',this.url('report',id));this.render();if(push)this.focusMain();
    }
    reportView() {
      const report=this.report;if(!report)return '';
      const result=report.result,dimensions=result.dimensions || [], narrative=result.narrative;
      const sorted=[...dimensions].sort((a,b)=>Number(b.mean)-Number(a.mean)),top=sorted[0],same=dimensions.every(d=>Number(d.mean)===Number(top.mean));
      const highest=dimensions.filter(d=>Number(d.mean)===Number(top.mean));
      const headline=same?'Médias semelhantes entre as dimensões':highest.map(d=>d.short_label || d.label).join(' e ');
      const subtitle=same?'Suas respostas ficaram com a mesma média nas dimensões deste questionário. Explore os exemplos de cada área para aprofundar a reflexão.':`Neste conjunto de respostas, ${highest.length===1?'a maior média aparece em':'as maiores médias aparecem em'} ${highest.map(d=>d.label.toLowerCase()).join(' e ')}. Relacione essa leitura às suas experiências e ao contexto da aplicação.`;
      return `<article class="pmp-report"><button type="button" class="pmp-link-button pmp-back pmp-no-print" data-action="nav" data-id="${report.is_own?'tests':'team'}">${icon('back')}${report.is_own?'Meus relatórios':'Resultados dos participantes'}</button><header class="pmp-report-header"><div><p class="pmp-eyebrow">RELATÓRIO INDIVIDUAL · ${esc(this.cfg.brand)}</p><h1>${esc(result.title)}</h1><div class="pmp-report-meta"><span>${icon('user')}${esc(report.participant_name)}</span><span>${icon('clock')}${date(report.completed_at)}</span><span>${icon('file')}Versão ${esc(result.module_version)}</span><span>${number(result.question_count)} respostas</span></div></div><button type="button" class="pmp-btn pmp-btn-secondary pmp-btn-small pmp-no-print" data-action="print">${icon('print')}Imprimir / salvar PDF</button></header><section class="pmp-report-hero"><div><p class="pmp-eyebrow">UM OLHAR SOBRE SUAS RESPOSTAS</p><h2>${esc(headline)}</h2><p>${esc(subtitle)}</p></div><div class="pmp-report-hero-value"><strong>${mean(top.mean)}</strong><small>/ 5</small><p>${same?'Média em cada dimensão':'Maior média neste questionário'}</p></div></section><div class="pmp-report-grid"><section class="pmp-card pmp-chart-card"><div class="pmp-chart-title"><h2>Seu mapa de respostas</h2><span>${dimensions.length} dimensões</span></div>${radar(dimensions)}<p class="pmp-chart-caption">Um panorama das médias em cada dimensão.</p></section><section class="pmp-card pmp-chart-card"><div class="pmp-chart-title"><h2>Dimensões em perspectiva</h2><span>Escala de 1 a 5</span></div><div class="pmp-bar-list">${dimensions.map((d,i)=>`<div><div class="pmp-bar-heading"><span>${esc(d.label)}</span><strong>${mean(d.mean)} <span class="pmp-muted">/ 5</span></strong></div><div class="pmp-bar-track" role="img" aria-label="${esc(d.label)}: média ${mean(d.mean)} em uma escala de 1 a 5"><div class="pmp-bar-fill" style="width:${Math.max(0,Math.min(100,Number(d.mean)/5*100))}%;--accent:${colors[i%colors.length]}"></div></div></div>`).join('')}</div><div class="pmp-chart-axis" aria-hidden="true"><span>0</span><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span></div><p class="pmp-chart-caption" style="margin-top:13px">As barras mostram médias, sem comparação com outras pessoas.</p></section></div>${this.narrativeView(narrative,dimensions)}<p class="pmp-report-note"><strong>Como ler este relatório.</strong> ${esc(result.interpretation)} Questionário autoral piloto, sem normas populacionais ou estudos de validação desta versão. ${esc(result.reference)}</p></article>`;
    }
    narrativeView(narrative, dimensions) {
      if(!narrative)return `<div class="pmp-insight-grid">${dimensions.map(d=>`<section class="pmp-card pmp-insight"><h3>${esc(d.label)}</h3><p>${esc(d.description)}</p><p>${esc(d.action)}</p></section>`).join('')}</div>`;
      const paragraphs=texts=>(texts || []).map(text=>`<p>${esc(text)}</p>`).join('');
      return `<section class="pmp-card pmp-narrative-summary" aria-labelledby="pmp-summary-title"><p class="pmp-eyebrow">SEU PERFIL EM PALAVRAS</p><h2 id="pmp-summary-title">Resumo detalhado do seu perfil</h2><div class="pmp-prose">${paragraphs(narrative.summary)}</div></section>
      <section class="pmp-card pmp-combination"><h2>${esc(narrative.combination_title)}</h2><div class="pmp-prose">${paragraphs(narrative.combination)}</div></section>
      <div class="pmp-section-top"><h2>Uma leitura de cada dimensão</h2>${badge('Baseada nas suas respostas')}</div>
      <div class="pmp-narrative-dimensions">${(narrative.dimensions || []).map((d,i)=>`<section class="pmp-card pmp-insight pmp-dimension-reading" style="--accent:${colors[i%colors.length]}"><div class="pmp-insight-head"><span class="pmp-insight-number">${String(i+1).padStart(2,'0')}</span><h3>${esc(d.label)}</h3><strong class="pmp-dimension-mean">${mean(d.mean)} / 5</strong></div>${badge(d.band_label)}<div class="pmp-prose"><p>${esc(d.reading)}</p>${d.nuance?`<p class="pmp-reading-nuance">${esc(d.nuance)}</p>`:''}<h4>Possibilidade para explorar</h4><p>${esc(d.opportunity)}</p><h4>Ponto de atenção</h4><p>${esc(d.attention)}</p><blockquote>${esc(d.reflection)}</blockquote></div><div class="pmp-insight-action"><span>Uma experiência possível</span><p>${esc(d.action)}</p></div></section>`).join('')}</div>
      <section class="pmp-card pmp-conversation"><p class="pmp-eyebrow">DO RELATÓRIO PARA O DIÁLOGO</p><h2>Uma conversa que pode ajudar</h2><div class="pmp-prose"><p>${esc(narrative.conversation)}</p></div></section>
      <div class="pmp-section-top"><h2>Seu plano de próximos passos</h2>${badge('7 · 14 · 30 dias')}</div><div class="pmp-plan-grid">${(narrative.plan || []).map(step=>`<section class="pmp-card pmp-plan-step"><span>${esc(step.when)}</span><h3>${esc(step.title)}</h3><p>${esc(step.text)}</p></section>`).join('')}</div><details class="pmp-reading-method"><summary>Como esta leitura foi construída</summary><p>${esc(narrative.method)}</p></details>`;
    }
    change(event) {
      const input=event.target;
      if(input.name==='pmp-ack'){const b=this.root.querySelector('[data-action="start"]');if(b)b.disabled=!input.checked;return;}
      if(input.name==='pmp-sharing'){const b=this.root.querySelector('[data-action="accept"]');if(b)b.disabled=!input.checked;return;}
      if(input.name!=='pmp-answer'||!this.attempt)return;
      this.attempt.answers[input.dataset.question]=Number(input.value);this.dirty=true;this.saveError=null;
      this.root.querySelectorAll('.pmp-option').forEach(label=>label.classList.toggle('is-selected',Boolean(label.querySelector('input:checked'))));
      const next=this.root.querySelector('[data-action="next"]');if(next)next.disabled=false;
      const count=Object.keys(this.attempt.answers).length,total=this.attempt.module.questions.length,progress=Math.round(count/total*100);
      const line=this.root.querySelector('.pmp-quiz-topline');if(line)line.innerHTML=`<span>${number(count)} de ${number(total)} respondidas</span><span>${number(progress)}% concluído</span>`;
      const track=this.root.querySelector('[role="progressbar"]');if(track)track.setAttribute('aria-valuenow',String(count));
      const fill=this.root.querySelector('.pmp-progress-fill');if(fill)fill.style.width=progress+'%';
      this.saveIndicator('Salvando sua resposta…');clearTimeout(this.saveTimer);this.saveTimer=setTimeout(()=>this.flush().catch(()=>{}),450);
    }
    saveIndicator(text,error=false) {
      const el=this.root.querySelector('[data-save]');if(!el)return;
      el.setAttribute('aria-live','polite');el.classList.toggle('is-error',error);el.innerHTML=icon(error?'clock':'check')+esc(text)+(error?' <button type="button" class="pmp-link-button" data-action="retry-save">Tentar novamente</button>':'');
    }
    async flush() {
      clearTimeout(this.saveTimer);
      if(this.savePromise)return this.savePromise;
      if(!this.dirty||!this.attempt)return;
      this.savePromise=(async()=>{
        while(this.dirty){
          const attempt=this.attempt,answers={...attempt.answers},revision=attempt.revision;this.dirty=false;
          try{const result=await this.api('attempts/'+attempt.id+'/answers',{answers,revision});attempt.revision=result.revision;this.saveError=null;this.saveIndicator('Progresso salvo na sua conta');}
          catch(error){this.dirty=true;this.saveError=error;this.saveIndicator('Não foi possível salvar.',true);this.toast(error.message);throw error;}
        }
      })().finally(()=>{this.savePromise=null;});
      return this.savePromise;
    }
    async submit(event) {
      const form=event.target, kind=form.dataset.form;
      if(!['invite','profile','billing'].includes(kind))return;
      event.preventDefault();if(!form.reportValidity())return;
      const button=form.querySelector('[type="submit"]');button.disabled=true;
      const fields=Object.fromEntries(new FormData(form).entries());
      const message=form.querySelector('[data-form-message]');if(message){message.textContent='';message.className='pmp-form-message';}
      try {
        let result;
        if(kind==='invite')result=await this.api('invites',fields);
        if(kind==='profile')result=await this.api('account/profile',fields);
        if(kind==='billing')result=await this.api('account/billing',fields);
        this.data=await this.api('state');
        if(kind!=='invite'){this.accountSettings=await this.api('account');if(this.accountSettings.logout_url)this.cfg.logout_url=this.accountSettings.logout_url;}
        this.render();this.toast(result.message);
        if(kind!=='invite'){
          const feedback=this.root.querySelector(`[data-form="${kind}"] [data-form-message]`);
          if(feedback){feedback.textContent=result.message;feedback.classList.add('is-success');}
        }
      } catch(error) {
        // Não conserva senhas em um formulário que retornou erro.
        form.querySelectorAll('input[type="password"]').forEach(input=>{input.value='';});
        if(message){message.textContent=error.message;message.classList.add('is-error');message.focus();}else this.toast(error.message);
      } finally {if(button.isConnected)button.disabled=false;}
    }
    async click(event) {
      const button=event.target.closest('[data-action]');if(!button || !this.root.contains(button))return;
      event.preventDefault();const action=button.dataset.action,id=button.dataset.id || '',key=action+':'+id;
      if(this.busy.has(key))return;this.busy.add(key);
      const lock=['claim','accept','start','release','complete','resend','cancel','more','more-orders','cancel-order'].includes(action);if(lock)button.disabled=true;
      try{
        if(action==='nav'){if(id==='catalog')this.catalogModule='';await this.go(id);}
        else if(action==='reload')await this.init();
        else if(action==='catalog-module'){this.catalogModule=id;await this.go('catalog');}
        else if(action==='all-modules'){this.catalogModule='';this.render();}
        else if(action==='claim'){const result=await this.api('claim',{module:id});this.data=await this.api('state');await this.openAttempt(result.attempt_id);}
        else if(action==='attempt')await this.openAttempt(Number(id));
        else if(action==='report')await this.openReport(Number(id));
        else if(action==='order')await this.openOrder(Number(id));
        else if(action==='more-orders'){
          const current=this.ordersList || this.data.orders;
          const next=await this.api('account/orders?page='+(current.page+1));
          this.ordersList={...next,items:[...current.items,...next.items]};this.render();
        }
        else if(action==='cancel-order'){
          if(!window.confirm('Cancelar este pedido ainda não pago?'))return;
          const result=await this.api('account/orders/'+Number(id)+'/cancel',{});this.order=result.order;this.data=await this.api('state');this.render();this.toast(result.message);
        }
        else if(action==='filter'){this.teamFilter=id;this.render();}
        else if(action==='auth-scroll')document.querySelector('.pmp-auth')?.scrollIntoView({block:'start',behavior:'smooth'});
        else if(action==='accept'){const consent=Boolean(this.root.querySelector('[name="pmp-sharing"]')?.checked);const result=await this.api('invites/accept',{token:this.cfg.invite_token,consent});this.cfg.invite_token='';this.data=await this.api('state');await this.openAttempt(result.attempt_id);}
        else if(action==='start'){const acknowledged=Boolean(this.root.querySelector('[name="pmp-ack"]')?.checked);await this.api('attempts/'+this.attempt.id+'/start',{acknowledged});await this.openAttempt(this.attempt.id,false);this.focusQuestion();}
        else if(action==='release'){await this.api('attempts/'+this.attempt.id+'/release',{});this.attempt=null;await this.go('packages');this.toast('O crédito voltou ao seu saldo disponível.');}
        else if(action==='next'){const q=this.attempt.module.questions[this.index];if(!this.attempt.answers[q.id])return;await this.flush();if(this.index===this.attempt.module.questions.length-1)this.review=true;else this.index++;this.render();this.focusQuestion();}
        else if(action==='prev'){await this.flush();this.index=Math.max(0,this.index-1);this.review=false;this.render();this.focusQuestion();}
        else if(action==='review'){await this.flush();this.review=true;this.render();}
        else if(action==='question'){await this.flush();this.index=Math.max(0,Math.min(this.attempt.module.questions.length-1,Number(id)));this.review=false;this.render();this.focusQuestion();}
        else if(action==='retry-save')await this.flush();
        else if(action==='complete'){await this.flush();const a=this.attempt;if(Object.keys(a.answers).length!==a.module.questions.length)throw new Error('Responda a todas as perguntas para concluir.');this.saveIndicator('Preparando seu relatório…');await this.api('attempts/'+a.id+'/complete',{answers:a.answers,revision:a.revision});this.dirty=false;this.data=await this.api('state');await this.openReport(a.id);}
        else if(action==='resend'){const result=await this.api('invites/'+id+'/resend',{});this.data=await this.api('state');this.render();this.toast(result.message);}
        else if(action==='cancel'){if(!window.confirm('Cancelar esta aplicação ainda não iniciada e devolver o crédito ao saldo?'))return;const result=await this.api('invites/'+id+'/cancel',{});this.data=await this.api('state');this.render();this.toast(result.message);}
        else if(action==='more'){const next=await this.api('records?kind='+encodeURIComponent(id)+'&page='+(this.data[id].page+1));this.data[id]={...next,items:[...this.data[id].items,...next.items]};this.render();}
        else if(action==='print')window.print();
      }catch(error){this.toast(error.message || 'Não foi possível concluir a operação.');}
      finally{this.busy.delete(key);if(lock&&button.isConnected)button.disabled=false;}
    }
    toast(message) {
      this.root.querySelector('.pmp-toast')?.remove();const node=document.createElement('div');node.className='pmp-toast';node.setAttribute('role','alert');
      const text=document.createElement('span');text.textContent=message;const close=document.createElement('button');close.type='button';close.textContent='×';close.setAttribute('aria-label','Fechar mensagem');close.addEventListener('click',()=>node.remove());node.append(text,close);this.root.append(node);setTimeout(()=>node.remove(),12000);
    }
  }
  const boot=()=>document.querySelectorAll('[data-pmp-app]').forEach(root=>{if(root.dataset.pmpReady)return;root.dataset.pmpReady='1';try{const config=JSON.parse(root.querySelector('.pmp-config').textContent);new App(root,config).init().catch(error=>{root.textContent=error.message;});}catch{root.textContent='Não foi possível iniciar a interface. Atualize a página.';}});
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
