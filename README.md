# ItIsAllMail / HabrMail

Парсит треды habr.com в почтовый ящик формата Maildir. Позволяет легче ориентироваться в длинных дискуссиях, а также использовать всю мощь вашего почтового клиента. Можно отслеживать изменения в тредах не имея аккаунта и не подписываясь на треды.

### Пример:

### Исходный тред в браузере:

![Страница в браузере](https://raw.githubusercontent.com/zargener/habrmail/master/doc/source_page.png)

### Тред в виде цепочки писем, открытых в Neomutt:
![Комментарии в виде почтового треда](https://raw.githubusercontent.com/zargener/habrmail/master/doc/thread_list.png)
![Статья в текстовом виде](https://raw.githubusercontent.com/zargener/habrmail/master/doc/article_texted.png)


## Использование

1. Создайте конфиг и список тредов для наблюдения:

        cd conf
        cp sources.yml.example sources.yml
        cp config.yml.example config.yml
        
2. Установите путь до почтового ящика в формате maildir в config.yml. По умолчанию вся почта пишется в `./maildir`. Для каждого source можно задавать в sources.yml свой почтовый ящик ключем "mailbox". Не забывайте создавать подпапки `new` и `cur`.

3. Запустите скрипт обновления тредов

        php fetcher.php
        
4. Откройте почтовый ящик в вашей любимой почтовой программе.

        neomutt -f maildir
        

## Добавление других сайтов

Для добавления нового сайта нужно:

1) Создать файл `lib/ItIsAllMail/Driver/DRIVER_NAME/Driver.php` с классом, реализующим интерфейс `ItIsAllMail\DriverInterface`. Имя класса может быть любым, главное чтобы класс в файле был один.

2) В `config.yml` добавьте с секцию `drivers` имя вашего драйвера `DRIVER_NAME`. Имя каталога, а не имя класса.

3) В `sources.yml` можете явно указывать код драйвера для обработки конкретного источника.
