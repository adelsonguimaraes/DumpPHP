# DumpPHP
 - descrição: Ferramenta para fazer backup de banco de dados MySQL via PHP.
 - Status: Em desenvolvimento;
 
 # Conceito
DumpPHP nasceu de uma necessidade e curiosidade pessoal para criar uma rotina de backup de banco de dados onde o acesso ao usuário fosse fácil e podesse ser acessado de qualquer dispositivo e local, como um pc, note, celular.
Tendo em vista a necessidade e a vontade de aprender, iniciei o levantamento de pesquisas e a produção desta pequena ferramenta para o auxílio em backup de projetos, atualmente a ferramenta encontra-se em desenvolvimento, abaixo listo o que já é possível fazer.

# O que a ferramenta faz hoje?
- O Sistema tem uma interface gráfica feita em BootStrap3;
![Tela Login](util/img/telalogin.PNG)

Acima temos a tela de Login, onde podemos configurar para acesso apenas de usuários do sistema.

![Tela inicial](util/img/telainicial.PNG)

Aqui temos a tela Principal onde o usuário pode fazer um novo backup, acessar a tela de configurações ou interagir com a grid de backups que já foram realizados, onde ele tem informação da data, que foi realizado, emails que estavam na lista para receber aviso de backup, e pode baixar um arquivo de backup ou deletar.

![Tela Configuracao](util/img/telaconfiguracao.PNG)

Por fim temos a tela de configuração, onde o usuário pode habilitar ou não o backup, assim permitindo ou impedindo a realização de novos backups, pode configurar o limite de intervalo entre os backups para que não haja uma realização em massa de backups antes que o tempo de limite seja atingido, pode definir os e-mails que iram receber a iinformação que o backup foi realizado, e também pode desativar se o sistema deve enviar ou não e-mails.
