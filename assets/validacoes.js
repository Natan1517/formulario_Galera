
const form = document.getElementById('formInscricao');
const nascimento = document.getElementById('nascimento');
const sucesso = document.getElementById('mensagemSucesso');
const campo = id => document.getElementById(id);
const apenasNumeros = valor => valor.replace(/\D/g, '');

const chaveRascunho = 'galeraTech_rascunho_inscricao';

function salvarRascunho() {
    const dados = {};

    form.querySelectorAll('input[name], select[name], textarea[name]').forEach(elemento => {
        if (elemento.type === 'hidden' || elemento.type === 'submit' || elemento.type === 'reset') {
            return;
        }

        dados[elemento.name] = elemento.type === 'checkbox' || elemento.type === 'radio'
            ? elemento.checked
            : elemento.value;
    });

    try {
        sessionStorage.setItem(chaveRascunho, JSON.stringify(dados));
    } catch (erro) {
        console.warn('Não foi possível salvar o progresso do formulário.', erro);
    }
}

function restaurarRascunho() {
    try {
        const salvo = sessionStorage.getItem(chaveRascunho);
        if (!salvo) return;

        const dados = JSON.parse(salvo);

        form.querySelectorAll('input[name], select[name], textarea[name]').forEach(elemento => {
            if (!(elemento.name in dados) || elemento.type === 'hidden') return;

            if (elemento.type === 'checkbox' || elemento.type === 'radio') {
                elemento.checked = dados[elemento.name];
            } else {
                elemento.value = dados[elemento.name];
            }
        });
    } catch (erro) {
        sessionStorage.removeItem(chaveRascunho);
        console.warn('Não foi possível recuperar o progresso do formulário.', erro);
    }
}

let consultaCep = 0;

function cpfValido(valor) {
    const cpf = apenasNumeros(valor);

    if (!/^\d{11}$/.test(cpf) || /^(\d)\1{10}$/.test(cpf)) return false;

    for (let pos = 9; pos <= 10; pos++) {
        let soma = 0;

        for (let i = 0; i < pos; i++) {
            soma += Number(cpf[i]) * (pos + 1 - i);
        }

        const digito = (soma * 10) % 11;

        if (Number(cpf[pos]) !== (digito === 10 ? 0 : digito)) return false;
    }

    return true;
}

function idadeEmAnos(data) {
    if (!data) return null;

    const partes = data.split('-');
    if (partes.length !== 3) return null;

    const ano = Number(partes[0]);
    const mes = Number(partes[1]) - 1;
    const dia = Number(partes[2]);
    const dataNascimento = new Date(ano, mes, dia);
    const hoje = new Date();

    if (
        dataNascimento.getFullYear() !== ano ||
        dataNascimento.getMonth() !== mes ||
        dataNascimento.getDate() !== dia ||
        dataNascimento > hoje
    ) return null;

    let idade = hoje.getFullYear() - ano;

    if (hoje.getMonth() < mes || (hoje.getMonth() === mes && hoje.getDate() < dia)) {
        idade--;
    }

    return idade;
}

function formatarCPF(valor) {
    let numero = apenasNumeros(valor).slice(0, 11);
    numero = numero.replace(/^(\d{3})(\d)/, '$1.$2');
    numero = numero.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
    numero = numero.replace(/\.(\d{3})(\d)/, '.$1-$2');
    return numero;
}

function formatarTelefone(valor) {
    let numero = apenasNumeros(valor).slice(0, 11);

    if (numero.length <= 2) return numero ? '(' + numero : '';

    return '(' + numero.slice(0, 2) + ') ' + numero.slice(2, 7) +
        (numero.length > 7 ? '-' + numero.slice(7) : '');
}

function atualizarResponsavel() {
    const idade = idadeEmAnos(nascimento.value);
    const secao = campo('secaoResponsavel');
    const obrigatorio = idade !== null && idade < 18;
    const campos = ['responsavelNome', 'parentesco', 'responsavelTelefone', 'responsavelEmail'];

    secao.hidden = !obrigatorio;

    campos.forEach(id => {
        const elemento = campo(id);
        elemento.required = obrigatorio;

        if (!obrigatorio) {
            elemento.value = '';
            elemento.setCustomValidity('');
        }
    });

    const asteriscoResponsavel = campo('asteriscoResponsavel');
    const asteriscoParentesco = campo('asteriscoParentesco');
    const asteriscoTelefoneResponsavel = campo('asteriscoTelefoneResponsavel');
    const ajudaResponsavel = campo('ajudaResponsavel');

    if (asteriscoResponsavel) {
        asteriscoResponsavel.textContent = obrigatorio ? '*' : '';
    }

    if (asteriscoParentesco) {
        asteriscoParentesco.textContent = obrigatorio ? '*' : '';
    }

    if (asteriscoTelefoneResponsavel) {
        asteriscoTelefoneResponsavel.textContent = obrigatorio ? '*' : '';
    }

    if (ajudaResponsavel) {
        ajudaResponsavel.textContent = obrigatorio
            ? 'Como o estudante tem menos de 18 anos, preencha os dados do responsável.'
            : 'Os dados do responsável aparecem somente para estudantes menores de 18 anos.';
    }
}

function atualizarIdade() {
    const idade = idadeEmAnos(nascimento.value);
    const texto = campo('idadeTexto');

    if (idade === null) {
        texto.textContent = 'Informe uma data de nascimento válida.';
        nascimento.setCustomValidity('Informe uma data de nascimento válida.');
    } else if (idade < 14 || idade > 24) {
        texto.textContent = 'Idade calculada: ' + idade + ' anos. A inscrição aceita idades de 14 a 24 anos.';
        nascimento.setCustomValidity('A idade deve estar entre 14 e 24 anos.');
    } else {
        texto.textContent = 'Idade calculada: ' + idade + ' anos.';
        nascimento.setCustomValidity('');
    }

    atualizarResponsavel();
    atualizarProgresso();
}

function atualizarProgresso() {
    const secoes = Array.from(form.querySelectorAll('section.secao'));
    let concluidas = 0;

    secoes.forEach((secao, indice) => {
        const etapa = document.querySelectorAll('.etapa-progresso')[indice];
        const campos = Array.from(secao.querySelectorAll('input[required], select[required], textarea[required]'));
        const completa = secao.hidden || (campos.length > 0 && campos.every(item => item.checkValidity()));

        if (completa) concluidas++;
        if (etapa) etapa.classList.toggle('concluida', completa);
    });

    const porcentagem = Math.round((concluidas / secoes.length) * 100);
    campo('barraProgresso').style.width = porcentagem + '%';
    campo('textoProgresso').textContent = concluidas + ' de ' + secoes.length + ' etapas concluídas';
    document.querySelector('.barra-progresso').setAttribute('aria-valuenow', porcentagem);
}

async function buscarCEP() {
    const entrada = campo('cep');
    const mensagem = campo('mensagemCep');
    const cep = apenasNumeros(entrada.value);
    const consultaAtual = ++consultaCep;

    entrada.setCustomValidity('');
    mensagem.textContent = '';
    mensagem.className = 'mensagem-cep';

    if (cep.length !== 8) {
        if (cep.length > 0) entrada.setCustomValidity('Informe um CEP com 8 números.');
        atualizarProgresso();
        return;
    }

    mensagem.textContent = 'Consultando CEP...';

    try {
        const resposta = await fetch('https://viacep.com.br/ws/' + cep + '/json/');
        if (!resposta.ok) throw new Error('Falha na consulta');

        const endereco = await resposta.json();
        if (consultaAtual !== consultaCep) return;

        if (endereco.erro) {
            ['logradouro', 'bairro', 'cidade', 'estado'].forEach(id => campo(id).value = '');
            entrada.setCustomValidity('CEP não encontrado. Confira os números.');
            mensagem.textContent = 'CEP não encontrado. Confira os números digitados.';
            mensagem.classList.add('erro');
        } else {
            campo('logradouro').value = endereco.logradouro || '';
            campo('bairro').value = endereco.bairro || '';
            campo('cidade').value = endereco.localidade || '';
            campo('estado').value = endereco.uf || '';
            entrada.setCustomValidity('');
            mensagem.textContent = 'Endereço encontrado. Confira os dados e complete o número.';
            mensagem.classList.add('sucesso');
        }
    } catch (erro) {
        if (consultaAtual !== consultaCep) return;
        entrada.setCustomValidity('Não foi possível consultar o CEP. Verifique sua conexão.');
        mensagem.textContent = 'Não foi possível consultar o CEP. Verifique sua conexão e tente novamente.';
        mensagem.classList.add('erro');
    }

    atualizarProgresso();
}

function carregarSugestoesEscola() {
    const lista = campo('listaEscolas');
    let escolas = [];

    try {
        escolas = JSON.parse(localStorage.getItem('escolasGaleraTech') || '[]');
    } catch (erro) {
        escolas = [];
    }

    lista.innerHTML = '';
    escolas.forEach(nome => {
        const opcao = document.createElement('option');
        opcao.value = nome;
        lista.appendChild(opcao);
    });
}

function salvarSugestaoEscola() {
    const nome = campo('escola').value.trim();
    if (nome.length < 3) return;

    try {
        let escolas = JSON.parse(localStorage.getItem('escolasGaleraTech') || '[]');
        escolas = escolas.filter(item => item.toLowerCase() !== nome.toLowerCase());
        escolas.unshift(nome);
        localStorage.setItem('escolasGaleraTech', JSON.stringify(escolas.slice(0, 20)));
        carregarSugestoesEscola();
    } catch (erro) {}
}

campo('cpf').addEventListener('input', evento => {
    evento.target.value = formatarCPF(evento.target.value);
    evento.target.setCustomValidity(cpfValido(evento.target.value) ? '' : 'CPF inválido.');
    atualizarProgresso();
});

campo('responsavelCpf').addEventListener('input', evento => {
    evento.target.value = formatarCPF(evento.target.value);
    evento.target.setCustomValidity(!evento.target.value.trim() || cpfValido(evento.target.value) ? '' : 'CPF do responsável inválido.');
});

campo('cep').addEventListener('input', evento => {
    const numero = apenasNumeros(evento.target.value).slice(0, 8);
    evento.target.value = numero.replace(/^(\d{5})(\d)/, '$1-$2');
    if (numero.length === 8) buscarCEP();
    else {
        consultaCep++;
        campo('mensagemCep').textContent = '';
        evento.target.setCustomValidity(numero.length ? 'Informe um CEP com 8 números.' : '');
        atualizarProgresso();
    }
});

campo('cep').addEventListener('blur', () => {
    if (apenasNumeros(campo('cep').value).length === 8) buscarCEP();
});

['telefone', 'responsavelTelefone'].forEach(id => {
    campo(id).addEventListener('input', evento => {
        evento.target.value = formatarTelefone(evento.target.value);
        evento.target.setCustomValidity(apenasNumeros(evento.target.value).length === 11 ? '' : 'Informe um telefone com DDD e 11 números.');
        atualizarProgresso();
    });
});

const hoje = new Date();
nascimento.max = hoje.getFullYear() + '-' + String(hoje.getMonth() + 1).padStart(2, '0') + '-' + String(hoje.getDate()).padStart(2, '0');
nascimento.addEventListener('change', atualizarIdade);
nascimento.addEventListener('input', atualizarIdade);
campo('escola').addEventListener('blur', salvarSugestaoEscola);

function validarCamposExtras() {
    const cpf = campo('cpf');
    const cpfResponsavel = campo('responsavelCpf');
    const cep = campo('cep');
    const telefone = campo('telefone');
    const telefoneResponsavel = campo('responsavelTelefone');
    const idade = idadeEmAnos(nascimento.value);

    cpf.setCustomValidity(cpfValido(cpf.value) ? '' : 'CPF inválido.');
    cpfResponsavel.setCustomValidity(!cpfResponsavel.value.trim() || cpfValido(cpfResponsavel.value) ? '' : 'CPF do responsável inválido.');
    cep.setCustomValidity(apenasNumeros(cep.value).length === 8 ? '' : 'Informe um CEP com 8 números.');
    telefone.setCustomValidity(apenasNumeros(telefone.value).length === 11 ? '' : 'Informe um telefone com DDD e 11 números.');

    if (!campo('secaoResponsavel').hidden) {
        telefoneResponsavel.setCustomValidity(apenasNumeros(telefoneResponsavel.value).length === 11 ? '' : 'Informe um telefone válido para o responsável.');
        const emailResponsavel = campo('responsavelEmail');
        emailResponsavel.setCustomValidity(emailResponsavel.validity.typeMismatch ? 'Informe um e-mail válido.' : '');
    }

    nascimento.setCustomValidity(idade !== null && idade >= 14 && idade <= 24 ? '' : 'A idade deve estar entre 14 e 24 anos.');
}

form.addEventListener('input', evento => {
    if (evento.target.id !== 'nascimento' && evento.target.id !== 'cpf' && evento.target.id !== 'cep') {
        evento.target.setCustomValidity('');
    }
    sucesso.classList.add('d-none');
    atualizarProgresso();
    salvarRascunho();
});

form.addEventListener('change', () => {
    sucesso.classList.add('d-none');
    atualizarProgresso();
    salvarRascunho();
});

form.addEventListener('submit', evento => {
    sucesso.classList.add('d-none');
    atualizarIdade();
    atualizarResponsavel();
    validarCamposExtras();
    form.classList.add('was-validated');
    atualizarProgresso();

    if (!form.checkValidity()) {
        evento.preventDefault();
        const primeiroInvalido = form.querySelector(':invalid');
        if (primeiroInvalido) primeiroInvalido.focus();
    } else {
        sessionStorage.removeItem(chaveRascunho);
    }
});

form.addEventListener('reset', () => {
    consultaCep++;
    sessionStorage.removeItem(chaveRascunho);
    form.classList.remove('was-validated');
    sucesso.classList.add('d-none');

    setTimeout(() => {
        form.querySelectorAll('input, select').forEach(elemento => elemento.setCustomValidity(''));
        campo('mensagemCep').textContent = '';
        campo('idadeTexto').textContent = 'A idade será calculada após informar a data.';
        atualizarResponsavel();
        atualizarProgresso();
    }, 0);
});

restaurarRascunho();
carregarSugestoesEscola();
atualizarResponsavel();
atualizarIdade();
atualizarProgresso();

const botao = document.getElementById("botaoTema");
const automatico = document.getElementById("temaAutomatico");
const temaNavegador = window.matchMedia("(prefers-color-scheme: dark)");

function aplicarTema(tema) {
    let escuro = tema === "escuro";

    if (tema === "automatico") {
        escuro = temaNavegador.matches;
    }

    document.body.classList.toggle("modo-escuro", escuro);
    botao.innerHTML = escuro ? "☀️" : "🌙";
}

function atualizarTema() {
    const temaSalvo = localStorage.getItem("tema") || "automatico";
    aplicarTema(temaSalvo);
}

botao.addEventListener("click", function () {
    const escuro = document.body.classList.contains("modo-escuro");
    const novoTema = escuro ? "claro" : "escuro";

    localStorage.setItem("tema", novoTema);
    aplicarTema(novoTema);
});

automatico.addEventListener("click", function () {
    localStorage.removeItem("tema");
    atualizarTema();
});

temaNavegador.addEventListener("change", function () {
    if (!localStorage.getItem("tema")) {
        atualizarTema();
    }
});

atualizarTema();
