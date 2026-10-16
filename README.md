# JosPortifolio — PHP + MySQL + MVC simples

## Requisitos
- PHP 8.2+
- MySQL 8.0+
- PDO MySQL ativo
- Apache/XAMPP

## Instalação
1. Coloque a pasta `josportifolio` dentro do `htdocs`.
2. Confirme `config/database.php`.
3. Importe `database/database.sql` no MySQL/phpMyAdmin.
4. Abra `http://localhost/josportifolio/`.
5. O painel fica em `http://localhost/josportifolio/admin.php`.

## Acesso inicial
- Email: `j@gmail.com`
- Palavra-passe: `123456`

A senha está gravada no SQL como hash e é validada com `password_verify()`.
Altere a senha depois do primeiro acesso.

## Mídia
Todas as imagens e ícones enviados pelo painel são gravados em `media.data` como `MEDIUMBLOB`.
O navegador recebe cada arquivo através de `media.php?id=...` com cache HTTP de 24 horas.

## Projetos
Cada projeto possui no máximo 4 slots de imagem:
- Slot 1: capa
- Slot 2: imagem adicional + descrição
- Slot 3: imagem adicional + descrição
- Slot 4: imagem adicional + descrição

A página inicial mostra por padrão até 3 projetos; esse limite pode ser alterado em `Seções` no painel.

## Segurança
- PDO com prepared statements e emulação de prepares desativada.
- CSRF nos formulários administrativos e de contacto/avaliação.
- `password_verify()` para autenticação.
- Uploads validados por MIME e tamanho antes de entrar no BLOB.
