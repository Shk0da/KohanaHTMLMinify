# kohana-html-minify
Минифицирует html (kohana, html, minify)

###Использование:
*1.* В application\bootstrap.php подключаем:

    Kohana::modules(array(
        ...
        'minifer' => MODPATH . 'minifer',
    ));

*2.* Добавляем где надо:

    $view = Minifer::minify_html($this->template->render());
    $this->response->body($view);

Получаем на выходе сжатый html.
