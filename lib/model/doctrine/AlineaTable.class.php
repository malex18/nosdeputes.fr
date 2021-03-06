<?php
/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class AlineaTable extends Doctrine_Table
{

  public function findOrCreate($loi, $article, $numero, $levels = array(0, 0, 0, 0)) {
    $art = Doctrine::getTable('ArticleLoi')->findOrCreate($loi, $article, $levels);
    $query = $this->createQuery('a')
      ->where('a.texteloi_id = ?', $loi)
      ->andWhere('a.article_loi_id = ?', $art->id)
      ->andWhere('a.numero = ?', $numero);
    $ali = $query->fetchOne();
    if (!$ali) {
      $ali = new Alinea();
      $ali->texteloi_id = $loi;
      $ali->numero = $numero;
    }
    $ali->setArticle($art);
    $ali->save();
    return $ali;
  }



}
