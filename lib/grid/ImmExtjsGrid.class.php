<?php
/**
 * extJs grid
 *
 */
class ImmExtjsGrid 
{
	/**
	 * default attributes for the grid
	 */
	public $attributes=array('loadMask'=>true,'frame'=>true,'bodyStyle'=>'border: 1px solid #8db2e3;','idxml'=>false);
	public $immExtjs=null;	
	public $privateName=null;
	public $contextMenu = array();
	public $actionsObject=null,$columns=null,$filters=array(),$proxy=null;
	public $menuactions_items=array();
    public $dataLoadedHandler = null;
	public $movedRowActions = 0;
	public $filter_types = array("boolean","numeric","list","string","combo","date");

	
	public function __construct($attributes=array())
	{		
		$this->immExtjs=ImmExtjs::getInstance();
		//for test
		$this->attributes['tbar']=array();			
		$this->attributes['stripeRows']=true;
		sfProjectConfiguration::getActive()->loadHelpers(array('ImmExtjsContextMenu'));
		if(isset($attributes['datasource']))
		{
                        if (isset($attributes['datasource']['dataLoadedHandler'])) {
                            $this->dataLoadedHandler = $attributes['datasource']['dataLoadedHandler'];
                        }
			unset($attributes['datasource']);
		}
		$this->privateName='grid_'.Util::makeRandomKey();
		
		if(isset($attributes['idxml'])&&$attributes['idxml'])
		{
			//$this->attributes['id']=$attributes['idxml'];
			$this->proxy['stateId']=$attributes['idxml'];
		}

		if(isset($attributes['plugins'])){
			$this->attributes['plugins'] = $attributes['plugins'];
			unset($attributes['plugins']);
		}
		$this->immExtjs->setAddons(array(
			'js' => array($this->immExtjs->getExamplesDir().'superboxselect/SuperBoxSelect.js'),
			'css'=>array($this->immExtjs->getExamplesDir().'superboxselect/superboxselect.css')
		));	
		$this->immExtjs->setAddons(array('js'=>array($this->immExtjs->getExamplesDir().'grid/Ext.ux.GridColorView.js',$this->immExtjs->getExamplesDir().'grid/Ext.ux.GroupingColorView.js')));
		$this->immExtjs->setAddons(array('js'=>array($this->immExtjs->getExamplesDir().'grid/Ext.ux.Grid.GroupingStoreOverride.js')));
		$this->immExtjs->setAddons(array('js'=>array($this->immExtjs->getExamplesDir().'plugins/Ext.ux.ExportUI.js')));
		
		$this->immExtjs->setAddons(array('js'=>array($this->immExtjs->getExamplesDir().'gridsummary/gridsummary.js')));
		$this->immExtjs->setAddons(array('css'=>array($this->immExtjs->getExamplesDir().'gridsummary/gridsummary.css')));
					
		
		if(isset($attributes['action'])&&$attributes['action'] !='n/a'){
			$attributes['url'] = $attributes['action'];			
		}
		//echo "<pre>";print_r($attributes);
		/*
		 * Check for expand button
		 */
		if(isset($attributes['expandButton']) && $attributes['expandButton']){
			$this->immExtjs->setAddons(array('js' => array($this->immExtjs->getExamplesDir().'grid/Ext.ux.plugins.AddExpandListButton.js') ));
			$this->attributes['plugins'][]="new Ext.ux.plugins.AddExpandListButton";
			if(!isset($this->attributes['tbar']))$this->attributes['tbar']=array();
			unset($attributes['expandButton']);
		}	
		/*
		* custom plugins...
		*/
		$this->addCustomPlugin($attributes);
		/*
		 * Grid Remote Search Test
		 */	
		/*$this->immExtjs->setAddons(array('js' => array($this->immExtjs->getExamplesDir().'search/js/Ext.ux.grid.Search.js') ));
		$this->immExtjs->setAddons(array('js' => array($this->immExtjs->getExamplesDir().'search/js/Ext.ux.IconMenu.js') ));
		$this->attributes['plugins'][]="new Ext.ux.grid.Search({
				iconCls:'icon-zoom'
				,readonlyIndexes:['note']
				,disableIndexes:['pctChange']
				,minChars:3
				,autoFocus:true
				,position:'bottom'
				,menuStyle:'radio'
			})";
		if(!isset($this->attributes['tbar']))$this->attributes['tbar']=array();*/
		//unset($attributes['expandButton']);
		
		/******** TEST ENDS HERE ****************************************************/
		
		$this->immExtjs->setAddons(array('js'=>array($this->immExtjs->getExamplesDir().'grid/RowExpander.js')));
		
		$attributes['tree']=(!isset($attributes['tree'])?false:$attributes['tree']);
		$attributes['select']=(!isset($attributes['select'])?false:$attributes['select']);
		
		$attributes['pager']=(!isset($attributes['pager'])?true:$attributes['pager']);
		
		$attributes['forceFit']=(!isset($attributes['forceFit'])?true:$attributes['forceFit']);		
		$attributes['remoteSort']=(!isset($attributes['remoteSort'])?false:$attributes['remoteSort']);
		
		if(isset($attributes['portal'])&&$attributes['portal']==true)
		{
			$this->attributes=array_merge($this->attributes,array('anchor'=> '100%',
															'frame'=>true,
															'collapsible'=>true,
															'draggable'=>true,
															'cls'=>'x-portlet'));
			$this->attributes['plugins'][] = 'new Ext.ux.MaximizeTool()';												
			unset($attributes['portal']);
		}
			
		if(isset($attributes['tools']))
		{
			$this->attributes['tools']=$attributes['tools']->end();
			
			unset($attributes['tools']);
		}
						
		$this->attributes['getWidgetConfig']=$this->immExtjs->asMethod(
			'var o={};
			 o.idxml=this.idxml || false;
			 return o;'
		);
		if(count($attributes)>0)
		$this->attributes=array_merge($this->attributes,$attributes);
	}
	
	private function resizeToolBars(){
		return '	
		var oc = '.$this->privateName.';		
		if(oc && oc.getTopToolbar()) oc.getTopToolbar().setWidth(oc.getWidth());
		if(oc && oc.getBottomToolbar()) oc.getBottomToolbar().setWidth(oc.getWidth());';		
	}
	public function addScripts(Array $scripts) {
		
		foreach($scripts as $script) {
			$this->immExtjs->setAddons(array('js'=>array($script)));
		}
		
	}
	
	public function startRowActions($attributes=array())
	{
		return new ImmExtjsGridActions($attributes);		
	}
	
	public function endRowActions($actionsObject)
	{
		$actionsObject->end();
		
		$this->actionsObject=$actionsObject;
	}
	
	public function addColumn($attributes=array())
	{
		$this->columns[]=$attributes;
	}
	
	public function addFilter($attributes=array())
	{
		$this->filters[]=$attributes;
	}
	
	public function updateTools($item,$key = false) {
		
		if($key === false) {
			if(isset($this->attributes["tools"])) {
				$key = sizeof($this->attributes["tools"]);
			} else {
				$key = 0;
			}	
		}
		
		$this->attributes["tools"][$key] = $item;
	}
	
	public function addButton($button)
	{
		if(!isset($this->attributes['tbar']))
		$this->attributes['tbar']=array();
		
		if(is_object($button))
		{
			array_push($this->attributes['tbar'],$this->immExtjs->asVar($button->end()));
		}
		else {
			array_push($this->attributes['tbar'],$this->immExtjs->asAnonymousClass($button));
		}
	}
	
	public function addHelp($html)
	{
		if(!isset($this->attributes['tbar']))
		{
			$this->attributes['tbar']=array();
		}
		
		$panel=new ImmExtjsPanel(array('html'=>'<div style="white-space:normal;">'.$html.'</div>','listeners'=>array('render'=>$this->immExtjs->asMethod(array("parameters"=>"panel","source"=>"if(panel.body){panel.body.dom.style.border='0px';panel.body.dom.style.background='transparent';}")))));
		@$this->attributes['listeners']['render']['source'].="var tb;if(this.getTopToolbar()&&this.getTopToolbar().items.items.length==0){tb = this.getTopToolbar();tb.addItem(".$panel->privateName.");}else{ tb = new Ext.Toolbar({renderTo: this.tbar,items: [".$panel->privateName."]});}if(tb&&tb.container){tb.container.addClass('tbarBottomBorderFix');}";
		
	}
	
	/**
	 * add a menu actions item
	 * ticket 1140
	 */
	public function addMenuActionsItem($attributes)
	{			
		$this->menuactions_items[]=$attributes;			
	}
	
	/**
	 * Add the export button to the grid's more action
	 *
	 */
	public function addMenuActionsExportButton($exportConfig)
	{		
		if(empty($exportConfig)) return;
		$h = '';
		$labels = array("csv"=>"CSV","firstx"=>"First 10000 records","current"=>"Current page","selected"=>"Selected records","pdf"=>"PDF");
		foreach($exportConfig as $k=>$v){
			foreach($v as $a=>$b){
				$h.=$k.$a.": function(){".$b."},";
				$exportConfig[$k][$a] = '';
			}
		} 
		$this->addMenuActionsItem(array('label'=>'Exports', 'icon'=>'/images/famfamfam/database_save.png','listeners'=>array('click'=> array('parameters'=>'','source'=>'
			new Ext.ux.ExportUI({
				width:400,'.$h.'							
				exportConfig:'.json_encode($exportConfig).',
				labels:'.json_encode($labels).'
			}).show();
		'))));		
	}
	
	/**
	 * constructing menuactions
	 * ticket 1140
	 */
	public function addMenuActions()
	{
		
		if(count($this->menuactions_items)>0)
		{		
			/**
			 * Fill to move menuactions button to the right
			 */
			new ImmExtjsToolbarFill($this);
			
			$menuactions_button=new ImmExtjsToolbarButton($this,array('label'=>'More Actions'));
			$menuactions_menu=new ImmExtjsToolbarMenu($menuactions_button);		
			
			foreach ($this->menuactions_items as $attributes)
			{
				$item=new ImmExtjsToolbarMenuItem($menuactions_menu,$attributes);$item->end();
			}		
			
			$menuactions_menu->end();
			$menuactions_button->end();
		}
	}
	
	public function setProxy($attributes=array())
	{	
		if(is_array($this->proxy))
		{
			$this->proxy=array_merge($this->proxy,$attributes);
		}
		else {
			$this->proxy=$attributes;
		}
	}
		
	public function end()
	{		
		/*$this->attributes['listeners']['afterrender']=$this->immExtjs->asMethod(array(
			"parameters"=>"value, metadata, record",
			"source"=>"var tb = this.getTopToolbar();
			if(!tb) return;	
			var box = this.getBox();
			tb.setWidth(box.width);	"
		));	
		*/	
		$this->attributes['canMask']=$this->immExtjs->asMethod(array("parameters"=>"","source"=>"return !Ext.isIE&&!".$this->privateName.".disableLoadMask&&!Ext.get('loading');"));
		
		if(!$this->attributes['tree'])
		{
			$this->attributes['view']=$this->immExtjs->GroupingColorView(array('forceFit'=>$this->attributes['forceFit'],'groupTextTpl'=>' {text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'));
		
		
			if(isset($this->attributes['clearGrouping'])&&$this->attributes['clearGrouping'])
			{
				@$this->attributes['listeners']['render']["source"].="this.store.clearGrouping();";
			}
		}
		else 
		{
			$this->immExtjs->setAddons(array('css'=>array($this->immExtjs->getExamplesDir().'treegrid/css/TreeGrid.css'),'js'=>array($this->immExtjs->getExamplesDir().'treegrid/TreeGrid.js',$this->immExtjs->getExamplesDir().'treegrid/Ext.ux.SynchronousTreeExpand.js')));
						
			$this->attributes['viewConfig']=$this->immExtjs->asAnonymousClass(array('forceFit'=>$this->attributes['forceFit']));
		}
		
		if(isset($this->proxy['url'])&&count($this->columns)>0)
		{
			$this->proxy['url']=sfContext::getInstance()->getRequest()->getRelativeUrlRoot().$this->proxy['url'];
			
			$filtersPrivateName='filters_'.Util::makeRandomKey();
			$storePrivateName='store_'.Util::makeRandomKey();
			$readerPrivateName='reader_'.Util::makeRandomKey();
			if($this->attributes['pager'])
			{
				$pagingToolbarPrivateName='pt_'.Util::makeRandomKey();
			}
			
			$wasSort=false;
			$firstSortableCol=null;
			
			$summaryPlugin = false;
			foreach ($this->columns as $column)
			{								
				$temp_column=null;
				$temp_field=null;
				$temp_name='Header '.Util::makeRandomKey();
				
				$temp_column['dataIndex']=isset($column['name'])?$column['name']:Util::stripText($temp_name);
				
				$temp_field['name']=isset($column['name'])?$column['name']:Util::stripText($temp_name);
				//$temp_field['type']=isset($column['type'])?$column['type']:'auto';
				$temp_field['sortType']=isset($column['sortType'])?$column['sortType']:'asText';
				$temp_column['sortType']=isset($column['sortType'])?$column['sortType']:'asText';
				
				//Grid summary plugin				
				$temp_column['summaryType']=isset($column['summaryType'])?$column['summaryType']:null;				
				if(!$summaryPlugin && $temp_column['summaryType']!=null){					
					$this->attributes['plugins'][]="new Ext.ux.grid.GridSummary";
					$summaryPlugin = true;
				}
						
				$temp_column['header']=isset($column['label'])?$column['label']:$temp_name;				
				$temp_column['sortable']=isset($column['sortable'])?$column['sortable']:true;
				if(isset($column['width'])&&$column['width']!='auto')
				{
					$temp_column['width']=$column['width'];
				}
				$temp_column['hidden']=isset($column['hidden'])?$column['hidden']:false;
				$temp_column['hideable']=isset($column['hideable'])?$column['hideable']:true;				
				$temp_column = $this->formatNumberColumn($temp_column);
				/**
				 * Edit link at defined column
				 * Please comment this block if the edit should be under the Actions column.
				 * This section looks the edit="true" in the xml columns. If found, and if 
				 * there is a row actions matching the name or label with edit, this will
				 * be transformed to the edit="true" column
				 */								
				if(((isset($column['edit']) && $column['edit'])) || (isset($column['action']))){				
					//print_r($this->actionsObject);					
					if($this->actionsObject){
						$actions = $this->actionsObject->getActions();									
						//print_r($actions);
						if(is_array($actions))						
						foreach($actions as $key=>$action){							
							if(
								(isset($column['action']) && preg_match("/list[0-9]+_".preg_replace("/^\//","",$column['action'])."$/",$action['name'])) 
								||
								(
									isset($column['edit'])
									&& 
									$column['edit'] === "true"
									&& 
									(preg_match("/_edit$/",$action['name']) || preg_match("/edit$/i",$action['label']) || preg_match("/_modify$/",$action['name']) || preg_match("/modify$/i",$action['label']) || preg_match("/_update$/",$action['name']) || preg_match("/update$/i",$action['label']))
								)
							){
								
								$urlIndex = $action['urlIndex'];															
								$credential = ComponentCredential::urlHasCredential($action['url']);
								$actionUrl = UrlUtil::url($action['url']);
								if(isset($action['load']) && $action['load'] == "page"){
									$actionUrl = $action['url'];
								}								
								$temp_column['renderer']=$this->immExtjs->asMethod(array(
									"parameters"=>"value, metadata, record",
									"source"=>"if(!".intval($credential).") return value;var action = record.get('".$urlIndex."'); if(!action) return value; var m = action.toString().match(/.*?\?(.*)/);return '<a  href=\"".$actionUrl."?'+m[1]+'\" qtip=\"".(isset($action['tooltip'])?$action['tooltip']:'')."\">'+ value + '</a>';"
								));							
								$this->actionsObject = $this->actionsObject->changeProperty($action['name'],'hidden',true);
								if(isset(ImmExtjs::getInstance()->private[$this->actionsObject->privateName]))
								unset(ImmExtjs::getInstance()->private[$this->actionsObject->privateName]);
								$this->actionsObject->end();
								$this->movedRowActions++;
							}						
						}
					}
				}
				
				
				/*
				 * check for context menu
				 */
				$style = '';
				$arrowSpan = '';
				if(isset($column['contextMenu']) && $column['contextMenu']){	
					$style = "";
					$arrowSpan = '<span class="interactive-arrow"><a class="interactive-arrow-a"  href="#">&nbsp;</a></span>';				
					$contextMenu = context_menu($column['contextMenu'])->privateName;
					$this->contextMenu[$temp_field['name']] = $contextMenu;
					$temp_column['renderer']=$this->immExtjs->asMethod(array(
							"parameters"=>"value, metadata, record",
							"source"=>"return '<span $style>$arrowSpan' + value + '</span>';"
					));
				}/*else{
					$contextMenu = context_menu('',array('grid'))->privateName;
					$this->contextMenu[$temp_field['name']] = $contextMenu;
				}*/
				/**********************************************************************************/
				if(isset($column['qtip'])&&$column['qtip'])
				{
					$temp_column['renderer']=$this->immExtjs->asMethod(array(
							"parameters"=>"value, metadata, record",
							"source"=>"var qtip = Ext.util.Format.htmlEncode(value); return '<span qtip=\"' + qtip + '\" $style>$arrowSpan' + value + '</span>';"
					));
				}
				//If numeric data, right align while rendering...
				// Disabled for now. JS error: "record is undefined"
				//$this->handleNumericColumns($temp_column);
				
				// Add filter here
				ImmExtjsGridFilter::add($this,$column,$temp_column,$temp_field);
				//Remote filter
				if(isset($column['sortIndex'])){
					$temp_column['sortIndex'] = $column['sortIndex'];
				}
				if(!isset($temp_column['id']))
				{
					$temp_column['id']=$temp_column['dataIndex'];
				}
				
				if(!$this->attributes['tree'])
				{
					if(isset($column['id'])&&$column['id'])
					{
						$temp_column['id']=$temp_column['dataIndex'];
						if(!isset($this->attributes[$readerPrivateName]['id']))
						{
							$this->attributes[$readerPrivateName]['id']=$temp_column['dataIndex'];
						}
					}
					
					if(isset($column['groupField'])&&$column['groupField'])
					{
						$this->attributes[$storePrivateName]['groupField']=$temp_column['dataIndex'];
					}
				}
				
				if(!$wasSort&&isset($column['sort'])&&in_array($column['sort'],array('ASC','DESC')))
				{					
					$wasSort=true;
					$this->defineSortInfo($storePrivateName, $temp_column['dataIndex'], $column['sort']);
				}

				if(!$firstSortableCol && ArrayUtil::isTrue($temp_column, 'sortable'))
				{
					$firstSortableCol=$temp_column['dataIndex'];
				}
								
				$this->attributes['columns'][]=$this->immExtjs->asAnonymousClass($temp_column);
				
				if($this->attributes['tree']&&!isset($this->attributes['master_column_id']))
				{					
					$this->attributes['master_column_id']=$temp_column['dataIndex'];
				}
				
				$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass($temp_field);
								
			}
			
			/*
			 * Add listeners for context menu
			 */
			$this->_addListenersForContextMenu($attributes);
			/**********************************************************/
			
			if (!$wasSort && $firstSortableCol)
			{
				$this->defineSortInfo($storePrivateName, $firstSortableCol, 'ASC');
			}
			
			$count_actions=(is_object($this->actionsObject)?count($this->actionsObject->attributes['actions']):0);
			
			if($count_actions>0)
			{
				for ($i=1;$i<=$count_actions;$i++)
				{
					$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'action'.$i,'type'=>'string'));
					$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'hide'.$i,'type'=>'boolean'));
				}
			}
			
			$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'message'));
			$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'redirect'));
			$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'load'));
			
			
			if($this->attributes['tree'])
			{								
				$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_id','type'=>'int'));
				$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_parent','type'=>'auto'));
				$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_is_leaf','type'=>'bool'));
				$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_color','type'=>'auto'));
				$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_cell_color','type'=>'auto'));
				$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_buttonOnColumn','type'=>'auto'));
				$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_buttonText','type'=>'auto'));
				$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_buttonDescription','type'=>'auto'));
				
				if($this->attributes['select'])
				{
					$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_selected','type'=>'auto'));
				}
				
				$this->attributes[$readerPrivateName]['id']='_id';
			}
			else {
				$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_color','type'=>'auto'));
				$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_cell_color','type'=>'auto'));
				//Select for normal grid too.....
				if($this->attributes['select'])
				{
					$this->attributes[$readerPrivateName]['fields'][]=$this->immExtjs->asAnonymousClass(array('name'=>'_selected','type'=>'auto'));
				}
				//..................................
				if(!isset($this->attributes[$readerPrivateName]['id']))
				{
					$this->attributes[$readerPrivateName]['id']='_id';
				}
			}
			
			$this->attributes[$readerPrivateName]['totalProperty']='totalCount';
			$this->attributes[$readerPrivateName]['root']='rows';
			$this->attributes[$readerPrivateName]['properties']='properties';
			
			$this->immExtjs->private[$readerPrivateName]=$this->immExtjs->JsonReader($this->attributes[$readerPrivateName]);
			unset($this->attributes[$readerPrivateName]);
			
			$this->attributes[$storePrivateName]['reader']=$this->immExtjs->asVar($readerPrivateName);
			if(isset($this->attributes['remoteSort']))
			{
				$this->attributes[$storePrivateName]['remoteSort']=$this->attributes['remoteSort'];
				unset($this->attributes['remoteSort']);
			}
			$this->attributes[$storePrivateName]['proxy']=$this->immExtjs->HttpProxy(array('url'=>$this->proxy['url'],'method'=>'GET','disableCaching'=>false,
				'listeners'=>array(
					'beforeload'=>$this->immExtjs->asMethod(array(
						'parameters'=>'proxy,params',
						'source'=>'proxy.lastParams=params')),
					'exception'=>self::getJsExceptionListener(
						$this->immExtjs, $this->privateName))
				));
			
			$beforeloadListener = "
				if(".$this->privateName.".canMask()){".$this->privateName.".getEl().mask('Loading, please Wait...', 'x-mask-loading');}
			";
			
			if(isset($this->proxy['stateId']))
			{
				$this->attributes[$storePrivateName]['pt_state_loaded']=false;
				$this->attributes[$storePrivateName]['pt_state']="Ext.state.Manager.get('".$this->proxy['stateId']."')";
				$this->attributes[$storePrivateName]['listeners']['beforeload']=$this->immExtjs->asMethod(array(
																			"parameters"=>"object,options",
																			"source"=>
																			"if(!this.pt_state_loaded&&this.pt_state){options.params=this.pt_state;this.pt_state_loaded=true;}".$beforeloadListener
																	));
			}
			else {
				$this->attributes[$storePrivateName]['listeners']['beforeload']=$this->immExtjs->asMethod(array(
																			"parameters"=>"object,options",
																			"source"=>$beforeloadListener
																	));
			}

			$this->attributes[$storePrivateName]['listeners']['load']=$this->immExtjs->asMethod(array(
																			"parameters"=>"object,records,options",
																			"source"=>
																			'if(records.length>0&&records[0].json.redirect&&records[0].json.message&&records[0].json.load){var rec=records[0].json;Ext.Msg.alert("Failure", rec.message, function(){afApp.load(rec.redirect,rec.load);});}else{if('.$this->privateName.'.canMask()){'.$this->privateName.'.getEl().unmask();}}
																			'.$this->privateName.'.ownerCt.ownerCt.doLayout();'
                                                                             .($this->dataLoadedHandler != '' ? "{$this->dataLoadedHandler}($this->privateName);" : '')
                                                                             .$this->resizeToolBars()                                                                             
																	));
			$this->attributes[$storePrivateName]['listeners']['loadexception']=$this->immExtjs->asMethod(array(
																			"parameters"=>"",
																			"source"=>
																			'if('.$this->privateName.'.canMask()){'.$this->privateName.'.getEl().unmask();}'
																	));
					
			if(!$this->attributes['tree'])
			{
				$this->immExtjs->private[$storePrivateName]=$this->immExtjs->GroupingStore($this->attributes[$storePrivateName]);
			}
			else{
				$this->immExtjs->private[$storePrivateName]=$this->immExtjs->AdjacencyListStore($this->attributes[$storePrivateName]);
			}
			unset($this->attributes[$storePrivateName]);
			
			if($this->attributes['pager'])
			{
				$this->attributes[$pagingToolbarPrivateName]['store']=$this->immExtjs->asVar($storePrivateName);
				$this->attributes[$pagingToolbarPrivateName]['displayInfo']=true;
				if(isset($this->attributes['pagerTemplate'])){
					$this->attributes[$pagingToolbarPrivateName]['displayMsg']=$this->parsePagerTemplate($this->attributes['pagerTemplate']);
				}
				$this->attributes[$pagingToolbarPrivateName]['pageSize']=isset($this->proxy['limit'])?$this->proxy['limit']:20;
							
				if(isset($this->proxy['stateId']))
				{
					$this->attributes[$pagingToolbarPrivateName]['stateId']=$this->proxy['stateId'];
					$this->attributes[$pagingToolbarPrivateName]['stateEvents']=array('change');
					$this->attributes[$pagingToolbarPrivateName]['stateful']=true;
					$this->attributes[$pagingToolbarPrivateName]['getState']=$this->immExtjs->asMethod(array(
																				"parameters"=>"",
																				"source"=>"return { start: ".(isset($this->proxy['start'])?$this->proxy['start']:"this.cursor").",
																									limit: this.pageSize };"
																		));			
				}
			
				if(count($this->filters)>0)
				{
					//$this->attributes[$pagingToolbarPrivateName]['plugins'] = $this->immExtjs->asVar($filtersPrivateName);
				}				
			
				if(!$this->attributes['tree'])
				{
					$this->immExtjs->private[$pagingToolbarPrivateName]=$this->immExtjs->PagingToolbar($this->attributes[$pagingToolbarPrivateName]);
				}
				else {
					$this->immExtjs->private[$pagingToolbarPrivateName]=$this->immExtjs->GridTreePagingToolbar($this->attributes[$pagingToolbarPrivateName]);
				}
				unset($this->attributes[$pagingToolbarPrivateName]);	
			}
			
		}
				
		if(count($this->filters)>0)
		{
			$this->attributes[$filtersPrivateName]['filters']=$this->filters;
			$this->attributes[$filtersPrivateName]['local']=(isset($this->attributes['remoteFilter']) && $this->attributes['remoteFilter'])?false:true;			
			//$this->attributes[$filtersPrivateName]['filterby']=sfContext::getInstance()->getActionStack()->getLastEntry()->getActionInstance()->getRequestParameter("filterby",false);
			$this->attributes[$filtersPrivateName]['filterby']=sfContext::getInstance()->getUser()->getAttribute('filterby',false);
			$this->attributes[$filtersPrivateName]['filterbyKeyword']=sfContext::getInstance()->getUser()->getAttribute('filterbyKeyword',false);
			sfContext::getInstance()->getUser()->setAttribute('filterby',false);
			sfContext::getInstance()->getUser()->setAttribute('filterbyKeyword',false);
			//$this->attributes['title'] = $this->attributes['title'].": <font color=red>(Filtered by keyword: '".$this->attributes[$filtersPrivateName]['filterbyKeyword']."'</font>)";
			$this->immExtjs->private[$filtersPrivateName]=$this->immExtjs->GridFilters($this->attributes[$filtersPrivateName]);
			
			$this->attributes['plugins'][]=$this->immExtjs->asVar($filtersPrivateName);
			
			unset($this->attributes[$filtersPrivateName]);	
		}
		
		if($count_actions>0)
		{
			if($this->movedRowActions){
				if(($count_actions - $this->movedRowActions)>0)
				$this->attributes['columns'][]=$this->immExtjs->asVar($this->actionsObject->privateName);
			}else{
				$this->attributes['columns'][]=$this->immExtjs->asVar($this->actionsObject->privateName);
			}			
			$this->attributes['plugins'][]=$this->immExtjs->asVar($this->actionsObject->privateName);
		}
		$this->attributes['store']=$this->immExtjs->asVar($storePrivateName);
		if($this->attributes['pager'])
		{
			$this->attributes['bbar']=$this->immExtjs->asVar($pagingToolbarPrivateName);
		}
		//changed to have select on normal grid too..
		//if($this->attributes['tree'] && $this->attributes['select'])
		if($this->attributes['select'])
		{
			$this->immExtjs->setAddons(array('js'=>array($this->immExtjs->getExamplesDir().'treegrid/Ext.ux.CheckboxSelectionModel.js')));
			
			$selectionModelPrivateName='sm_'.Util::makeRandomKey();
			$this->immExtjs->private[$selectionModelPrivateName]=$this->immExtjs->UxCheckboxSelectionModel(array());
			$this->attributes['sm']=$this->immExtjs->asVar($selectionModelPrivateName);
			//if($this->attributes['tree'])			
			$this->attributes['columns'][]=$this->immExtjs->asVar($selectionModelPrivateName);
			//array_unshift($this->attributes['columns'],$this->immExtjs->asVar($selectionModelPrivateName));
			
			/*
			 * Since the insertion of checkbox selection model at the beginning of the grid, the tree structrue get lost, though it was 
			 * fine for non-tree grid. To overcome this first the grid is rendered as it is with the checkbox selection model at the end
			 * and when the grid is rendered the checkbox selection model is now moved to the initial column position of the grid.
			 */
			$jsSource = "
				var gcm = ".$this->privateName.".getColumnModel();
				if(gcm.getColumnHeader(gcm.getColumnCount()-1) == '<div class=\"x-grid3-hd-checker\" id=\"hd-checker\">&#160;</div>') 
				gcm.moveColumn(gcm.getColumnCount()-1,0);
				";
		}
		else {
			$jsSource = '';
		}
		
		@$this->attributes['listeners']['render']["source"].="
			this.store.load({
				params:{
					start:".(isset($this->proxy['start'])?$this->proxy['start']:0).", 
					limit:".(isset($this->proxy['limit'])?$this->proxy['limit']:20)."
				}
			});";
		
		$attributes['listeners']['render']["source"]=$this->attributes['listeners']['render']["source"];
		$attributes['listeners']['render']["source"] .= $jsSource;
		unset($this->attributes['listeners']['render']["source"]);
		
		$this->attributes['listeners']['render']=$this->immExtjs->asMethod(array(
				"parameters"=>"",
				"source"=>$attributes['listeners']['render']["source"]
		));		
		
		unset($attributes['listeners']['render']["source"]);
		
		//attach center loading ajax to links with class="widgetLoad"
		$this->attributes['listeners']['mouseover']=$this->immExtjs->asMethod(array(
				"parameters"=>"",
				"source"=>"afApp.attachHrefWidgetLoad();"
		));
						
		if(count($this->filters)>0)
		{		
			$this->immExtjs->setAddons(array('js'=>array($this->immExtjs->getExamplesDir().'grid-filtering/ux/menu/EditableItem.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/menu/ComboMenu.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/menu/RangeMenu.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/GridFilters.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/DrillFilter.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/RePositionFilters.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/SaveSearchState.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/FilterInfo.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/FilterOption.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/filter/Filter.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/filter/BooleanFilter.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/filter/ComboFilter.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/filter/DateFilter.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/filter/ListFilter.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/filter/NumericFilter.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/filter/StringFilter.js',$this->immExtjs->getExamplesDir().'grid-filtering/ux/grid/filter/TextFilter.js'),'css'=>array($this->immExtjs->getExamplesDir().'grid-filtering/resources/style.css')));
			// Add reset filters on menu action if there is filter in grid
			$this->addMenuActionsItem(array('label'=>'Filters','icon'=>'/images/famfamfam/drink.png','listeners'=>array('click'=>array('parameters'=>'','source'=>'var grid = '.$this->privateName.';
							var filters = grid.filters;
							if(!filters) return;							
							var saveFilter = Ext.ux.SaveSearchState(grid);
							saveFilter.viewSavedList();'))));				
			$this->addMenuActionsItem(array('xtype'=>'menuseparator'));			
			$savedFilters = afSaveFilterPeer::getFiltersByName(isset($this->attributes['name'])?$this->attributes['name']:$this->attributes['path']);
			$fc = 0;
			foreach($savedFilters as $f){
				//if($fc > 4) break;			
				$this->addMenuActionsItem(array('label'=>++$fc.". ".$f->getName(),'listeners'=>array('click'=>array('parameters'=>'','source'=>'
							var grid = '.$this->privateName.';
							var filters = grid.filters;
							if(!filters) return;							
							var saveFilter = Ext.ux.SaveSearchState(grid);							
							saveFilter.restore(\''.$f->getFilter().'\',"'.$f->getName().'");'))));	
			}
		}
		$this->addMenuActions();
		
		if(!$this->attributes['tree'])
		{
			$this->immExtjs->private[$this->privateName]=$this->immExtjs->GridPanel($this->attributes);
		}
		else {
			$this->immExtjs->private[$this->privateName]=$this->immExtjs->GridTreePanel($this->attributes);
		}
		//print_r($this);
	}

	private function defineSortInfo($storePrivateName, $field, $direction)
	{
		$this->attributes[$storePrivateName]['sortInfo']=$this->immExtjs->asAnonymousClass(array('field'=>$field,'direction'=>$direction));
	}
	private function _addListenersForContextMenu(&$attributes){
		/*
		 * Listener for the context menu
		 *
		 */
		$initialize = '';
				
		foreach($this->contextMenu as $key=>$value){				
			$initialize .= "contextMenus['".$key."'] = ".$value.";";	
		}
		$this->attributes['listeners']['click'] = "function(e){		
			var t = e.getTarget();													
			if(t.className != 'x-grid3-header'){
	            var r = e.getRelatedTarget();
	            var v = this.view;
	            var ci = v.findCellIndex(t.parentNode);
	            var ri = v.findRowIndex(t);		            
	            var grid = this;
	            //alert(ci); alert(ri);            
	            if(ci === false || ri === false) return ;
				if(ci === -1 || ri === -1) return ;
	            var cell = this.getView().getCell(ri,ci);
	          
	            if(t.className == 'interactive-arrow-a'){
	           
	            	ci = v.findCellIndex(t.parentNode.parentNode);
	            	var contextMenus = Array();
					".$initialize."	
					var fieldName = grid.getColumnModel().getDataIndex(ci);
					var data = null;
					if(grid.getSelectionModel){					
						grid.getSelectionModel().clearSelections();
						grid.getSelectionModel().selectRow(ri);
						var record = grid.getSelectionModel().getSelected();
						data = record.get(fieldName);					
						grid.getSelectionModel().clearSelections();
					}
					var xy = e.getXY();
					
					if(data == null || data == ''){
						var valueNode = Ext.DomQuery.selectNode('.interactive-arrow-a',grid.getView().getCell(ri,ci));
						data = valueNode.innerHTML;						
					}
					var pattern = /(\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b)/;
					var ip = data.match(pattern);
					if(!ip){
						ip = data.match(/[0-9]+/);
						if(!ip){
							Ext.Msg.alert('Notice','No valid data found');
							return;
						}
					}					
					data = ip[0];
					
					var contextMenu = contextMenus[fieldName];
					if(contextMenu && data != ''){
						contextMenu.stack['text'] = data;
						contextMenu.stack['grid'] = grid;
						contextMenu.stack['ri'] = ri;
						contextMenu.stack['ci'] = ci;
						contextMenu.stack['cell'] = grid.getView().getCell(ri,ci);
						contextMenu.stack['cellDiv'] = grid.getView().getCell(ri,ci).getElementsByTagName('div')[0];			
						contextMenu.stack['rowDivs'] = grid.getView().getRow(ri).getElementsByTagName('div');
						contextMenu.showAt(xy);
					}		
	            }
	            if(t.className == 'grid-util-action'){
	            	gridUtil(this,t.rel);
	            }
	            if(Ext.ux.DrillFilter)	            	
	            new Ext.ux.DrillFilter(grid,e);
	        }          
            
		}";
		$this->attributes['listeners']['mouseout'] = "function(e){
			var t = e.getTarget();
			if(t.className != 'x-grid3-header'){
	            var r = e.getRelatedTarget();
	            var v = this.view;
	            var ci = v.findCellIndex(t);
	            var ri = v.findRowIndex(t);	            
	            if(ci === false || ri === false) return ;
				if(ci === -1 || ri === -1) return ;
	            var cell = this.getView().getCell(ri,ci);
	            if(cell){		            
	            	
	            	//Cross browser implementation
	            	var className = 'interactive-arrow-active';
	            	var tagName = 'span', _tags = cell.getElementsByTagName(tagName), _nodeList = [];
				    for (var i = 0, _tag; _tag = _tags[i++];) {
				        if (_tag.className.match(new RegExp('(\\s|^)'+className+'(\\s|$)'))) {
				            _nodeList.push(_tag);
				        }
				    }
				    //.............................................................................						            	
	            
		            var arrowDiv = _nodeList[0];
		            if(arrowDiv){
		            	arrowDiv.className = 'interactive-arrow';			            
		            }
	            }
            }	            
		}";
		$this->attributes['listeners']['mouseover'] = "function(e){			
			var t = e.getTarget();				
			if(t.className != 'x-grid3-header'){
	            var r = e.getRelatedTarget();
	            var v = this.view;
	            var ci = v.findCellIndex(t);
	            var ri = v.findRowIndex(t);
	           
	            if(ci === false || ri === false) return ;
				if(ci === -1 || ri === -1) return ;
	            var cell = this.getView().getCell(ri,ci);		           		           
	            if(cell){		            
	            	
	            	//Cross browser implementation
	            	var className = 'interactive-arrow';
	            	var tagName = 'span', _tags = cell.getElementsByTagName(tagName), _nodeList = [];
				    for (var i = 0, _tag; _tag = _tags[i++];) {
				        if (_tag.className.match(new RegExp('(\\s|^)'+className+'(\\s|$)'))) {
				            _nodeList.push(_tag);
				        }
				    }
				    //.............................................................................
						            	
	            
		            var arrowDiv = _nodeList[0];
		            if(arrowDiv){
		            	arrowDiv.className = 'interactive-arrow-active';			            	
		            }
	            }
            }
            
		}";
	}
	
	public function postAsTraditional($params=array()){
		/**
		 * Submitting the data through traditional form
		 * @var unknown_type
		 */	
		$ts = '';
		$randId = "traditional_form_".rand(1,999999999); 
		if(isset($params['preSource'])){
			$ts.=$params['preSource']."\r\n";
		}
		$method = isset($params['method'])?$params['method']:"POST";		
		$ts .= '
			var randId = "traditional_form_"+Math.floor(Math.random()*11);
			var div = document.createElement("div");
			var form = document.createElement("form");
			form.name=randId;
			form.action = "'.$params['action'].'";
			form.method = "'.$method.'";			
		';
		if(isset($params['params']) && is_array($params['params'])){
			foreach($params['params'] as $key=>$value){
														
				$ts .= '					
					var field = document.createElement("input");
					field.name = "'.$key.'";					
					form.appendChild(field);
				';
				if($value[1] == "string"){
					$ts.='field.value = "'.$value[0].'"';
				}else if($value[1] == "js"){
					$ts.='field.value = '.$value[0];
				}
			}
		}
		$ts.='	
			div.appendChild(form)		
			div.style.display="none"
			document.body.appendChild(div)
			form.submit();			
		';
		if(isset($params['postSource'])){
			$ts.=$params['postSource']."\r\n";
		}
		return $ts;
		/******************************************************************************/	
	}

	
	public function getFileExportJsUrl($exportType='page',$af_format = "csv") {
		$params = array('af_format'=>$af_format);
		if($exportType === 'all') {
			$params['start'] = 0;
			$params['limit'] = sfConfig::get("app_parser_max_items");
		}

		return 'Ext.urlAppend('.$this->privateName.'.store.proxy.url, Ext.urlEncode(Ext.applyIf('.json_encode($params).', '.$this->privateName.'.store.proxy.lastParams||{})))';
	}
	
	/**
	* The numeric data in grids are right aligned
	* @author: prakash paudel
	*
	* Caution: Every data in grid's cell that are numeric will be right aligned
	* it may be mixed align for same column
	*/
	private function handleNumericColumns(&$temp_column){		
		$headerRight = '
			var cm = '.$this->privateName.'.getColumnModel();
			cm.setColumnHeader(colIndex,"<div align=\"right\">"+cm.getColumnHeader(colIndex)+"</div>");
		';
		$preRenderer = isset($temp_column['renderer'])?"value = ".$temp_column['renderer']."();":"";				
		$temp_column['renderer']=$this->immExtjs->asMethod(array(
			"parameters"=>"value, metaData, record, rowIndex, colIndex, store",
			"source"=>$preRenderer." if(!value) return value; var stripped = value.toString().replace(/<\S[^><]*>/g,'').replace(/^\s+/g, '').replace(/\s+$/g, ''); var regex = /^(\d|-)?(\d|,)*\.?\d*$/ ;if(regex.test(stripped)){ ".$headerRight." return '<div align=\"right\">'+value+'</div>';} return value;"
		));					
	}
	private function parsePagerTemplate($template){
		$replacement = array(
			"(start)"=>"{0}",
			"(end)"=>"{1}",
			"(total)"=>"{2}"
		);
		foreach($replacement as $key=>$value){
			$template = str_replace($key,$value,$template);
		}
		$return = isset($template)?$template:'Displaying {0}-{1} of {2}';
		return $return;
	}
	private function addCustomPlugin($attributes){
		/**
		 * Plugins for the grid
		 */
		
		if(isset($attributes['plugin']) && $attributes['plugin']){
			/*if($attributes['plugin'] == "index_search"){
				$this->immExtjs->setAddons(array('js' => array($this->immExtjs->getExamplesDir().'grid/Ext.ux.plugins.IndexSearch.js') ));
				$this->attributes['plugins'][]="new Ext.ux.plugins.IndexSearch";
			}*/
			if($attributes['plugin'] == "row_order"){
				$this->immExtjs->setAddons(array('js' => array($this->immExtjs->getExamplesDir().'plugins/grid-row-order/Ext.ux.plugins.GridRowOrder.js') ));
				$this->immExtjs->setAddons(array('css' => array($this->immExtjs->getExamplesDir().'plugins/grid-row-order/row-up-down.css') ));
				$this->attributes['plugins'][]='new Ext.ux.plugins.GridRowOrder()';
			}
			if(preg_match('/^custom:(.*)$/', $attributes['plugin'], $match)){
				$plugin = $match[1];
				if(file_exists(sfConfig::get('sf_root_dir')."/web/js/custom/".$plugin.".js")){
					$this->immExtjs->setAddons(array('js' => array("/js/custom/".$plugin.".js") ));			
				}else if(file_exists(sfConfig::get('sf_root_dir')."/plugins/appFlowerPlugin/web/js/custom/".$plugin.".js")){
					$this->immExtjs->setAddons(array('js' => array("/appFlowerPlugin/js/custom/".$plugin.".js") ));			
				}
				
				if(file_exists(sfConfig::get('sf_root_dir')."/web/css/".$plugin.".css")){
					$this->immExtjs->setAddons(array('css' => array("/css/".$plugin.".css") ));			
				}else if(file_exists(sfConfig::get('sf_root_dir')."/plugins/appFlowerPlugin/web/css/".$plugin.".css")){
					$this->immExtjs->setAddons(array('css' => array("/appFlowerPlugin/css/".$plugin.".css") ));			
				}			
				$this->attributes['plugins'][]='new '.$plugin.'()';
			}
		}
	}

	private static function getJsExceptionListener($immExtjs, $gridPrivateName) {
		return $immExtjs->asMethod(array(
			'parameters'=>'proxy,type,action,options,response',
			'source'=>'
	var message = "Unable to load the data.";
	var onClose = undefined;
	try {
		if(response.responseText){
			var json = Ext.decode(response.responseText);
			message = json.message || message;
			if(json.redirect) {
				onClose = function(){afApp.load(json.redirect,json.load);};
			}
		}
	} catch(expected) {
	}

	if('.$gridPrivateName.'.canMask()){
		'.$gridPrivateName.'.getEl().unmask();
	}
	Ext.Msg.alert("Failure", message, onClose);
	'));
	}
	private function formatNumberColumn(&$column){
		if(in_array($column['sortType'],array("asSize","htmlAsSize","asInt","htmlAsInt","asFloat","htmlAsFloat"))){
			$column['align'] = "right";
		}
		return $column;
	}
}
