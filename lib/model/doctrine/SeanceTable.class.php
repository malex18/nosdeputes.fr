<?php
/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class SeanceTable extends Doctrine_Table
{
  public function findOne ($type, $date, $heure, $session, $commissiontxt = null) {
    if ($type == 'commission') {
      $commission = Doctrine::getTable('Organisme')->findOneByNomOrCreateIt($commissiontxt, 'parlementaire');
      if(!$commission)
	return null;
      return $commission->getSeanceByDateAndMoment($date, $heure, $session);
    }
    return  $this->createQuery('s')->where('type = ?', $type)->andWhere('date = ?', $date)->andWhere('moment = ?', $heure)->fetchOne();
  }
  public function findOneOrCreateIt($type, $date, $heure, $session, $commissiontxt = null) {
    $s = $this->findOne($type, $date, $heure, $session, $commissiontxt);
    if (!$s) {
      if ($type == 'commission') {
	$commission = Doctrine::getTable('Organisme')->findOneByNomOrCreateIt($commissiontxt, 'parlementaire');
	return $commission->getSeanceByDateAndMomentOrCreateIt($date, $heure, $session);
      }
      if ($type != 'hemicycle')
	return new Exception("Cannot create seance of type $type");
      $s = new Seance();
      $s->type = $type;
      $s->date = $date;
      $s->moment = $heure;
      $s->setSession($session);
      $s->save();
    }
    return $s;
  }

  public function findByOrganismeidDateAndMoment($organisme_id, $date, $moment) {
    $q = Doctrine::getTable('Seance')->createQuery('s');
    $q->where("organisme_id = ?", $organisme_id)->andWhere('date = ?', $date)->andWhere('moment = ?', $moment);
    return $q->fetchOne();
  }

  public function getPager($request, $query = NULL)
  {
    $pager = new sfDoctrinePager('Seance',30);
    $pager->setQuery($query);
    $pager->setPage($request->getParameter('pages', 1));
    $pager->init();
    return $pager;
  }

}
