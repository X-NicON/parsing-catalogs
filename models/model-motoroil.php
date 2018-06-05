<?php
class Motoroil {
  private $domain = 'http://motoroil24.ru';
  private $utils;
  private $save;

  //Страницы каталога
  private $categories = array(
    //'/catalog/oils/auto/', //Автомасла
    //'/catalog/oils/mototsikly/',
    //'/catalog/oils/gidrotsikly/',
    //'/catalog/oils/lodki/',
    //'/catalog/oils/gruzoviki/',
    '/catalog/oils/gruzoviki/?SECTION_CODE=gruzoviki&PAGEN_1=9',
    '/catalog/oils/industrialnye-masla-sozh/',

    '/catalog/filters/air/',
    '/catalog/filters/hydraulic/',
    '/catalog/filters/oil/',
    '/catalog/filters/salon/',
    '/catalog/filters/fuel/',
    '/catalog/filters/antifreeze/',
    '/catalog/filters/industry/',
    //'', //Аксессуары только Ключи для снятия фильтров

    '/catalog/technical-fluids/antifrizy/',
    '/catalog/technical-fluids/voda-distillirovannaya/',
    '/catalog/technical-fluids/zhidkost-dlya-gur/',
    '/catalog/technical-fluids/germetiki/',
    '/catalog/technical-fluids/omyvateli-stekla/',
    '/catalog/technical-fluids/ochistitely/',
    '/catalog/technical-fluids/promyvochnye-masla-i-prisadki/',
    '/catalog/technical-fluids/razmorazhivateli/',
    '/catalog/technical-fluids/tormoznye-zhidkosti/',
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

        $nextUrl = htmlspecialchars_decode($this->domain.$nextUrl);

        Utils::logMessage($nextUrl."\n", 'addProduct');

        $html = str_get_html($this->utils->readUrl($nextUrl));

        if($html == false ){
          var_dump($nextUrl);
          var_dump($this->utils->readUrl($nextUrl));
          var_dump($html);
          sleep(1);
          continue;
        }

        $products_wrap = $html->find('.product-item-list', 0);
        if($products_wrap != null) {
          $products_items = $products_wrap->children();
          $cat = $this->parse_category($html);
        }else{
          $products_items = array();
        }

        foreach($products_items as $item) {

          //Боже, чилдрен берёт даже html комментарий, отсекаем
          if(!isset($item->class) || $item->class == 'clear') {
            continue;
          }

          Utils::logMessage($item->find('h2 a', 0)->href."\n", 'addProduct');

          $product_url = $item->find('h2 a', 0)->href;
          $product_url = htmlspecialchars_decode($this->domain.$product_url);
          $product_html = str_get_html($this->utils->readUrl($product_url));

          if($product_html != false){

            $sku = $this->parse_sku($product_html);

            if($sku != false) {
              $name = $this->parse_name($product_html);
              $product_img_url = $this->parse_images($product_html);

              $new_name_img = $this->utils->saveImage($product_img_url, $sku);

              $result['product'][] = array(
                'SKU' => $sku,
                'Name' => $name,
                'Price' => $this->parse_price($product_html),
                'Category' => implode(' / ', $cat),
                'Instock' => $this->parse_instock($product_html),
                'Image' => $new_name_img,
                'Description' => $this->parse_description($product_html),
                'Attributes' => $this->parse_attributes($product_html),
              );

              //Utils::logMessage($name."\n", 'addProduct');
            }
          }
        }

        if(isset($result['product']) && !empty($result['product'])){
          //Добавляем все элементы в конец xml файла
          $this->save->appendSaveXML($result);
        }

      } while ($nextUrl = $this->while_next_url($html, $cat_url));

      //Utils::logMessage('!!!PAGES '.$i."\n", 'addProduct');
    }
  }

  private function while_next_url($html, $cat_url) {
    $pagger = $html->find('.paginator', 0);
    //Если пагинации на странице нет
    if($pagger == null) {
      return false;
    }

    $nextpage = $pagger->find('.current',0);
    if($nextpage == null) {
      return false;
    }

    $nextpage = $nextpage->next_sibling();
    if($nextpage == null) {
      return false;
    }

    return $cat_url.$nextpage->href;
  }

  private function parse_category($html) {
    $breadcrumb = $html->find('.breadcrumbs', 0);
    $cat = array(
      $breadcrumb->children(2)->plaintext,
      str_replace('  			 				 				Главная 			 				  				Каталог масел 			 				  				', '', $breadcrumb->plaintext),
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
    return $html->find('.page-title h1 span', 0)->plaintext;
  }

  private function parse_price($html) {
    if($html->find('.js-price', 0) != null) {
      return str_replace(' ','', str_replace(',','.', $html->find('.js-price', 0)->plaintext));
    }
    return 0;
  }

  private function parse_sku($html, $uniq = false) {
    if($uniq == true){
      $sku = gmmktime();
    }else{
      $sku_field = $html->find('.l-code__item-detail', 0);

      if($sku_field != null){
        $sku = str_replace('Код продукта: ','', $sku_field->plaintext);
      }else{
        $sku = false;
      }
    }

    return $sku;
  }

  private function parse_attributes($html) {
    $div_attrs = $html->find('.b-harak_list-content', 0);
    if($div_attrs != null) {
      $list_attrs = array();

      $block_attrs = $div_attrs->find('.b-harak_table');

      foreach ($block_attrs as $block) {
        foreach ($block->children(0)->children() as $attr) {
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

    $main_img = $html->find('.open-popup img', 0);

    if($main_img != null){
      $imgs_links = strtok($main_img->src, '?');

      if(basename($imgs_links) == 'no_foto_0.png'){
        $imgs_links = '';
      }
    } else {
      $imgs_links = '';
    }

    $purl = parse_url($imgs_links);

    if(!isset($purl['host'])) {
      $imgs_links = $this->domain.$imgs_links;
    }

    return $imgs_links;
  }

  private function parse_description($html) {
    $desc = $html->find('.short-description__inner', 0);

    if($desc != null){
      $desc = trim($desc->innertext);
    } else {
      $desc = '';
    }

    return $desc;
  }

  private function parse_instock($html) {
    $stock = $html->find('.l-status__item img', 0)->alt;

    if($this->parse_price($html) == 0) {
      $stock = false;
    }

    //Если есть элемент "Ожидается", значит нет в наличии
    if($stock != 'В наличии') {
      $instock = 'outofstock';
    }else{
      $instock = 'instock';
    }
    return $instock;
  }
}

?>
