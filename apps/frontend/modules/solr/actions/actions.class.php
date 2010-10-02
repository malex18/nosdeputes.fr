<?php

/**
 * solr actions.
 *
 * @package    cpc
 * @subpackage solr
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12479 2008-10-31 10:54:40Z fabien $
 */
class solrActions extends sfActions
{

  private function getPhoto($obj) {
    sfProjectConfiguration::getActive()->loadHelpers(array('Url'));  
    switch(get_class($obj)) {
    case 'Intervention':
      if ($obj->getParlementaire()->__toString()) {
        return $this->getPartial('parlementaire/photoParlementaire', array('parlementaire'=>$obj->getParlementaire(), 'height'=>70));
      }
    case 'QuestionEcrite':
      return $this->getPartial('parlementaire/photoParlementaire', array('parlementaire'=>$obj->getParlementaire(), 'height'=>70));
    case 'Amendement':
      return '';
    case 'Parlementaire':
      return $this->getPartial('parlementaire/photoParlementaire', array('parlementaire'=>$obj, 'height'=>70));
    case 'Commentaire':
      return '<img width="53" src="'.url_for('@photo_citoyen?slug='.$obj->getCitoyen()->getSlug()).'"/>';
    case 'Citoyen':
      return '<img width="53" src="'.url_for('@photo_citoyen?slug='.$obj->getSlug()).'"/>';
    }
  }
 /**
  * Executes index action
  *
  * @param sfRequest $request A request object
  */
  public function executeSearch(sfWebRequest $request)
  {
    if ($search = $request->getParameter('search')) {
      return $this->redirect('solr/search?query='.$search);
    }
    $this->query = $request->getParameter('query');
    
    $query = preg_replace('/\*/', '', $this->query);

    if (!strlen($query)) {
      $query = '*';
    }

    $nb = 20;
    $deb = ($request->getParameter('page', 1) - 1) * $nb ;
    $fq = '';
    $this->facet = array();

    $this->selected = array();
    if ($on = $request->getParameter('object_name')) {
      $this->selected['object_name'][$on] = 1;
      $fq .= " object_name:$on";
    }
    if ($tags = $request->getParameter('tag')) {
      foreach(explode(',', $tags) as $tag) {
        $this->selected['tag'][$tag] = 1;
        $fq .= ' tag:"'.$tag.'"';
      }
    }
    //Récupère les résultats auprès de SolR
    $params = array('hl'=>'true', 'fl' => 'id,object_id,object_name,date', 'hl.fragsize'=>500, "facet"=>"true", "facet.field"=>array("object_name","tag"), "facet.date" => "date", "facet.date.start"=>"2007-05-01T00:00:00Z", "facet.date.end"=>"NOW", "facet.date.gap"=>"+1MONTH", 'fq' => $fq);
    $this->sort_type = 'pertinence';

    $this->sort = $request->getParameter('sort');
    $date = $request->getParameter('date');
    $from = $request->getParameter('from');
    $format = $request->getParameter('format');

    $this->tags = 0;
    if ($format) {
      sfConfig::set('sf_web_debug', false);
      $this->tags = $request->getParameter('tags');
      $this->format = $format;
    }

    $this->title = $request->getParameter('title');

    if ($format == 'rss') {
      $this->setTemplate('rss');
      $this->feed = new sfRssFeed();
      $this->feed->setLanguage('fr');
      $this->sort = 1;
      $date = null;
      $from = null;
    }

    if ($format == 'json') {
      $this->getResponse()->setContentType('text/plain; charset=utf-8');
      $this->setTemplate('json');
      $this->setLayout(false);
    }

    if ($format == 'xml') {
      $this->getResponse()->setContentType('text/xml; charset=utf-8');
      $this->setTemplate('xml');
      $this->setLayout(false);
    }

    if ($format == 'csv') {
      // $this->getResponse()->setContentType('application/csv; charset=utf-8');
      $this->getResponse()->setContentType('text/plain; charset=utf-8');
      $this->setTemplate('csv');
      $this->setLayout(false);
    }

    if ($this->sort) {
      $this->selected['sort'] = 1;
      $params['sort'] = "date desc";
      $this->sort_type = 'date';
    }
    
    $this->vue = 'par mois';
    
    if ($date) {
      $this->selected['date'][$date] = $date;
      $dates = explode(',', $date);
      list($from, $to) = $dates;
      
      $nbjours = round((strtotime($to) - strtotime($from))/(60*60*24)-1);
      $jours_max = 90; // Seuil en nb de jours qui détermine l'affichage par jour ou par mois 
      
      $comp_date_from = explode("T", $from);
      $comp_date_from = explode("-", $comp_date_from[0]);
      $comp_date_from = mktime(0, 0, 0, $comp_date_from[1] + 1, $comp_date_from[2], $comp_date_from[0]);
      $comp_date_from = date("Y-m-d", $comp_date_from).'T00:00:00Z';
      
      // Affichage d'un jour
      if($from == $to) {
        $period = 'DAY';  
        $this->vue = 'ce jour'; 
      }
      // Affichage d'un mois
      if($comp_date_from == $to) {
        $period = 'DAY';
        $this->vue = 'le mois de';
      }
      // Affichage d'une période
      if(($nbjours < $jours_max) and ($from != $to) and ($comp_date_from != $to)) { 
        $period = 'DAY';
        $to = $to.'+1DAY';
        $this->vue = 'par jour';
      } 
      if($nbjours > $jours_max) { 
        $period = 'MONTH';
        $to = $to.'+1MONTH';
        $this->vue = 'par mois';
      }
      
      $query .= ' date:['.$from.' TO '.$to.']';
      $params['facet.date.start'] = $from;
      $params['facet.date.end'] = $to;
      $params['facet.date.gap'] = '+1'.$period;
    }
    
    $this->start = $params['facet.date.start'];
    $this->end = $params['facet.date.end'];

    try {
      $s = new SolrConnector();
      $results = $s->search($query, $params, $deb, $nb);
    }
    catch(Exception $e) {
      $results = array('response' => array('docs' => array(), 'numFound' => 0));
      $this->getUser()->setFlash('error', 'Désolé, le moteur de recherche est indisponible pour le moment');
    }
    
    //Reconstitut les résultats
    $this->results = $results['response'];
    for($i = 0 ; $i < count($this->results['docs']) ; $i++) {
      $res = $this->results['docs'][$i];
      $obj = $res['object'];
      $this->results['docs'][$i]['link'] = $obj->getLink();
      $this->results['docs'][$i]['photo'] = $this->getPhoto($obj);
      $this->results['docs'][$i]['titre'] = $obj->getTitre();
      $this->results['docs'][$i]['personne'] = $obj->getPersonne();
      if (isset($results['highlighting'][$res['id']]['text'])) {
        $high_res = array();
        foreach($results['highlighting'][$res['id']]['text'] as $h) {
          $h = preg_replace('/.*=/', '', $h); 
          array_push($high_res, $h);
        }
        $this->results['docs'][$i]['highlighting'] = preg_replace('/^'."$this->results['docs'][$i]['personne']".'/', '', implode('...', $high_res));
      } 
      else $this->results['docs'][$i]['highlighting'] = '';
    }
    
    $this->results['end'] = $deb + $nb;
    $this->results['page'] = $deb/$nb + 1;
    if ($this->results['end'] > $this->results['numFound'] && $this->results['numFound']) {
      $this->results['end'] = $this->results['numFound'] + 1;
    }

    //Prépare les facets
    $this->facet['parlementaire']['prefix'] = 'parlementaire=';
    $this->facet['parlementaire']['facet_field'] = 'tag';
    $this->facet['parlementaire']['name'] = 'Parlementaire';

    if (isset($results['facet_counts'])) {
      $this->facet['type']['prefix'] = '';
      $this->facet['type']['facet_field'] = 'object_name';
      $this->facet['type']['name'] = 'Types';
      $this->facet['type']['values'] = $results['facet_counts']['facet_fields']['object_name'];
      
      $tags = $results['facet_counts']['facet_fields']['tag'];
      $this->facet['tag']['prefix'] = '';
      $this->facet['tag']['facet_field'] = 'tag';
      $this->facet['tag']['name'] = 'Tags';
      foreach($tags as $tag => $nb ) {
        if (!$nb)
        continue;
        if (!preg_match('/=/', $tag))
          $this->facet['tag']['values'][$tag] = $nb;
        if (preg_match('/^parlementaire=(.*)/', $tag, $matches)) {
          $this->facet['parlementaire']['values'][$matches[1]] = $nb;
        }
      }
    }
    
    if (!$results['response']['numFound']) {
      if ($format)
      return ;
      return $this->setTemplate('noresults');
    }
    $this->fdates = array();
    $this->fdates['max'] = 1;
    foreach($results['facet_counts']['facet_dates']['date'] as $date => $nb) {
      if (preg_match('/^20/', $date)) {
        $pc = $nb/$results['response']['numFound'];
        $this->fdates['values'][$date] = array('nb' => $nb, 'pc' => $pc);
        if ($this->fdates['max'] < $pc) {
          $this->fdates['max'] = $pc;
        }
      }
    }
  }
  
  public function executeRedirect(sfWebRequest $request)
  {
    $add = '';
    if ($p = $request->getParameter('format')) {
      $add .= '&format='.$p;
    }
    if ($p = $request->getParameter('object_name')) {
      $add .= '&object_name='.$p;
    }
    if ($p = $request->getParameter('slug')) {
      $add .= '&tag=parlementaire='.preg_replace('/\-/', '+', $p);
    }
    return $this->redirect('solr/search?query='.$request->getParameter('query').$add);
  }
}
