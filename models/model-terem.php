<?php

class Terem {
  private $domain = 'http://www.teremonline.ru';
  private $utils;
  private $save;

  //Страницы каталога
  private $categories = array(
    '/products?f[0]=gruppa_553%3A62434&view=0', //Котлы
    '/products?f[0]=gruppa_553%3A62413&view=0', //Насосы
    '/products?f[0]=gruppa_553%3A62401&view=0', //Радиаторы
    '/products?f[0]=gruppa_553%3A62426&view=0', //Приборы учёта
    '/products?f[0]=gruppa_553%3A62418&view=0', //Водонагреватели
    '/products?f[0]=gruppa_553%3A62424&view=0', //Мембранные баки
    '/products?f[0]=gruppa_553%3A62384&view=0', //Запорно-регулирующая арматура
    '/products?f[0]=gruppa_553%3A62425&view=0', //Предохранительная арматура
    '/products?f[0]=gruppa_553%3A62430&view=0', //Трубы
    '/products?f[0]=gruppa_553%3A62428&view=0', //Теплоизоляция
    '/products?f[0]=gruppa_553%3A62432&view=0', //Фитинги
    '/products?f[0]=gruppa_553%3A62431&view=0', //Фильтры
    '/products?f[0]=gruppa_553%3A62415&view=0', //Приборы управления
    '/products?f[0]=gruppa_553%3A62433&view=0', //Электрооборудование
    '/products?f[0]=gruppa_553%3A62429&view=0', //Теплый пол
    '/products?f[0]=gruppa_553%3A62427&view=0', //Расходные материалы
    '/products?f[0]=gruppa_553%3A62416&view=0', //Горелки для котлов
    '/products?f[0]=gruppa_553%3A62420&view=0', //Дымоходы
    '/products?f[0]=gruppa_553%3A62414&view=0', //Коллекторы и коллекторные группы
    '/products?f[0]=gruppa_553%3A64707&view=0', //Арматура для котельной
    '/products?f[0]=gruppa_553%3A62412&view=0', //Конвекторы
    '/products?f[0]=gruppa_553%3A62423&view=0', //Контрольно-измерительные приборы и автоматика
    '/products?f[0]=gruppa_553%3A62422&view=0', //Инструмент для монтажа
    '/products?f[0]=gruppa_553%3A62421&view=0', //Ёмкости для жидкостей
    '/products?f[0]=gruppa_553%3A65984&view=0', //Сантехника
  );

  public function __construct(){
    $this->utils = new Utils;
    $this->save  = new XMLSaveFromArray();
  }

  public function parse_site() {

    foreach ($this->categories as $cat_url) {
      $nextUrl = $cat_url;

      $i = 0;
      $productsCount = 0;

      do {
        //освобождаем память под новые товары на странице
        $result = array();

        $i++;

        if(isset($nextUrl->href)){
          $nextUrl = $nextUrl->href;
        }

        $nextUrl = htmlspecialchars_decode($this->domain.$nextUrl);

        Utils::logMessage($nextUrl."\n", 'addProduct');

        $html = str_get_html($this->utils->readUrl($nextUrl));

        $products_wrap = $html->find('.catalog-table .view-content', 0);
        if($products_wrap != false) {
          $products_items = $products_wrap->children();
        }else{
          $products_items = array();
        }

        foreach($products_items as $item) {
          $product_url = $item->find('a', 0)->href;
          $product_url = htmlspecialchars_decode($this->domain.$product_url);
          $product_html = str_get_html($this->utils->readUrl($product_url));

          if($product_html != false){
            $sku = $this->parse_sku($product_html);

            if($sku != false) {
              $name = $this->parse_name($product_html);
              $product_img_url = $this->parse_images($product_html);

              $result['product'][] = array(
                'SKU' => $sku,
                'Name' => $name,
                'Price' => $this->parse_price($product_html),
                'Category' => implode(' / ', $this->parse_category($product_html)),
                'Instock' => $this->parse_instock($product_html),
                'Image' => basename($product_img_url),
                'Description' => $this->parse_description($product_html),
                'Attributes' => $this->parse_attributes($product_html),
              );

              $this->utils->saveImage($product_img_url);
              //Utils::logMessage($name."\n", 'addProduct');
            }
          }
        }

        if(isset($result['product']) && !empty($result['product'])){
          //Добавляем все элементы в конец xml файла
          $this->save->appendSaveXML($result);
        }

        $pagger = $html->find('ul.pager', 0);
        //Если пагинации на странице нет
        if($pagger == false) {
          break;
        }

        $nextpage = $pagger->find('.pager-current',0);
        if($nextpage == null) {
          break;
        }

        $nextpage = $nextpage->next_sibling();
        if($nextpage == null) {
          break;
        }

      } while ($nextUrl = $nextpage->find('a', 0));

      //Utils::logMessage('!!!PAGES '.$i."\n", 'addProduct');
    }
  }

  private function parse_category($html) {
    $breadcrumb = $html->find('.breadcrumb', 0);
    $cat = array(
      $breadcrumb->children(2)->plaintext,
      $breadcrumb->children(3)->plaintext,
    );

    return $cat;
  }

  private function parse_attr_brand($html) {
    $breadcrumb = $html->find('.breadcrumb', 0);

    if($breadcrumb->children(4) !== null && $breadcrumb->children(4)->tag == 'a'){
      return $breadcrumb->children(4)->plaintext;
    }

    return '';
  }

  private function parse_name($html) {
    return $html->find('.product-title', 0)->plaintext;
  }

  private function parse_price($html) {
    return str_replace(' ','', str_replace(',','.', $html->find('.price-value', 0)->plaintext));
  }

  private function parse_sku($html, $uniq = false) {
    if($uniq == true){
      $sku = gmmktime();
    }else{
      $sku_field = $html->find('.field-name-field-code', 0);

      if(isset($sku_field)){
        $sku = $html->find('.field-name-field-code', 0)->find('.field-item', 0)->plaintext;
      }else{
        $sku = false;
      }
    }

    return $sku;
  }

  private function parse_attributes($html) {
    $div_attrs = $html->find('#tab-full-properties', 0);
    $list_attrs = array(
      'attribute'=>
        array(
          array(
            'name'  => 'Бренд',
            'value' => $this->parse_attr_brand($html)
          )
        )
    );

    if(isset($div_attrs)){
      $block_attrs = $div_attrs->find('.block-th-title');
      foreach ($block_attrs as $block) {
        $section_name = $block->plaintext;
        foreach ($block->next_sibling()->children() as $attr) {
          $list_attrs['attribute'][] = array(
            'name'  => trim($attr->children(0)->plaintext),
            'value' => trim($attr->children(1)->plaintext)
          );
        }
      }
    }

    return $list_attrs;
  }

  private function parse_images($html) {

    $main_img = $html->find('[itemprop=image]', 0);

    if(isset($main_img)){
      $imgs_links = strtok($main_img->src, '?');

      if(basename($imgs_links) == 'no_foto_0.png'){
        $imgs_links = '';
      }
    } else {
      $imgs_links = '';
    }

    return $imgs_links;
  }

  private function parse_description($html) {
    $desc = $html->find('#tab-description', 0);

    if(isset($desc)){
      $desc = trim($desc->innertext);
    } else {
      $desc = '';
    }

    return $desc;
  }

  private function parse_instock($html) {
    $stock = $html->find('.product-status-out-of-stock', 0);

    //Если есть элемент "Ожидается", значит нет в наличии
    if(isset($stock)){
      $instock = 'outofstock';
    }else{
      $instock = 'instock';
    }
    return $instock;
  }
}

?>
