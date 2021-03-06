/*
 *  Dynamic widgets
 *  @author1: Prakash Paudel  
 *  @author2: Radu Topala
 */
Ext.ns('afApp');

function strstr (haystack, needle, bool) {   
    var pos = 0;    
    haystack += '';
    pos = haystack.indexOf( needle );
    if (pos == -1) {
        return false;
    } else{
        if (bool){
            return haystack.substr( 0, pos );
        } else{
            return haystack.slice( pos );
        }
        return true;
    }
}
function in_array (needle, haystack, argStrict) {   
    var key = '', strict = !!argStrict;
    if (strict) {
        for (key in haystack) {
            if (strstr(haystack[key],needle)) {
                return true;
            }
        }
    } else {
        for (key in haystack) {
        	
            if (strstr(haystack[key],needle)) {            	
                return true;
            }
        }
    }
    return false;
}

String.prototype.ucfirst = function () {
	   
    return this.substr(0,1).toUpperCase()+this.substr(1);
}

Array.prototype.in_array = function (needle, argStrict) {
	   
    var key = '', strict = !!argStrict, haystack=this;

    if (strict) {
        for (key in haystack) {
            if (haystack[key] === needle) {
                return true;
            }
        }
    } else {
        for (key in haystack) {
            if (haystack[key] == needle) {
                return true;
            }
        }
    }

    return false;
}
/**
* pack logic for window
*/
afApp.pack = function(win,winConfig,Application){
	var winConfig = winConfig || {};
	Application = Application?Application : App; //App is default application for all Appflower apps
	var viewport=Application.getViewport();
	win.on("show",function(win){
		if(winConfig.applyTo) return;		
		var childs = win.findBy(function(component,container){
			return true;
		});
		if(childs && childs[0]){
			var firstChild = childs[0];
			var vpWidth = viewport.getBox().width;
			var vpHeight = viewport.getBox().height;
			var winWidth = firstChild.getBox().width+35;
			var winHeight = firstChild.getBox().height+35;
			winWidth = winWidth>vpWidth?(0.7*vpWidth):winWidth;
			winHeight = winHeight>vpHeight?(0.7*vpHeight):winHeight;
			win.setSize(winWidth,winHeight);
			win.center();
		}
		/*
		* By some reason if the window head moved out of 
		* viewport visibility range, bring back it.
		*/
		var pos = win.getPosition(); 
		if(pos[1]<0) win.setPosition(pos[0],0);
		
		/*
		* On window move, do not let the window to completely go out of 
		* viewport range, keep some portion of window always visible
		*/
		win.on("move",function(win,x,y){
			if(y<0) win.setPosition(x,0);
			if(x < 100-win.getWidth()) win.setPosition(100-win.getWidth(),y);
			if(x > Ext.getBody().getWidth()-100) win.setPosition(Ext.getBody().getWidth()-100,y);
			if(y > Ext.getBody().getHeight()-100) win.setPosition(x,Ext.getBody().getHeight()-100);
		});
	});
}
afApp.executeAddons = function(addons,json,title,superClass,winConfig,Application){
	Application = Application?Application : App; //App is default application for all Appflower apps
	var viewport=Application.getViewport();
	var counter = 0;
	var backup = new Array();
	var finish;
	var load = function(){	
		if(counter >= addons.length){
			finish();
			return;
		}
		//mask = new Ext.LoadMask(Ext.get("body"), {msg: "<b>Loading additional addons.....</b> <br>Please wait..<br>"+(counter+1)+" of "+addons.length+" addon(s) are loaded.",removeMask:true});
		//mask.show();		
		afApp.loadingProgress(viewport.layout.center.panel.getEl(),(counter+1)/addons.length);
		var nextAddon=addons[counter++];
		
		afApp.createAddon(nextAddon,false,load);
	};

	finish = function(){
				backupForms();
				eval(json.source);								
				Ext.applyIf(winConfig, {
					autoScroll : true,
					maximizable : true,
					draggable:true,					
					closeAction:'hide',
					
					items : new Ext.Panel( {
						frame : winConfig.applyTo?false:true,	
						width:"auto",
						layout:"form",
						items : (function(){ return eval(json.center_panel) })()
					})
				});
				
				if(winConfig.applyTo){
					winConfig = Ext.apply(winConfig,{
						frame:false
					});
					var win = new Ext.Panel( winConfig );					
				}else{
					var win = new Ext.Window( winConfig );
				}
				if(title) win.setTitle(title);
				
				if(win.doLayout) win.doLayout()
				if(win.show) win.show();
				
				/* window resize, pack and onmove adjustments */
				afApp.pack(win,winConfig,Application);
				
				if(win.doLayout) win.doLayout()
				if(win.show) win.show();				
				if(win.center) win.center();
				win.on("render",function(win){eval(json.public_source);},null,{single:true});
				
				afApp.loadingProgress(viewport.layout.center.panel.getEl(),1);
				//mask.hide();			
				
				win.on("hide",function(){	
					if(superClass)superClass.onHide(win);									
					win.destroy();
					win.close();
					restoreBackup();
				})
	};

	function restoreBackup(){
		for(id in backup){
			var el = document.getElementById(id);
			if(el){
				el.id = backup[id]
			}
		}
	}
	function backupForms(comp){		
		var randomnumber=Math.floor(Math.random()*11);
		var randomId = "x-form-el-random-"+randomnumber;
		var inputs = document.getElementsByTagName("input");
		var textareas = document.getElementsByTagName("textarea");
		var selects = document.getElementsByTagName("select");
		
		var arr = new Array();
		arr.push(inputs);
		arr.push(textareas);
		arr.push(selects);
		for(var j=0;j<arr.length;j++){
			var forms = arr[j];
			for(var i=0;i<forms.length;i++){
				if(forms[i].id){
					if(forms[i].id.match("edit")){					
						var el = document.getElementById("x-form-el-"+forms[i].id);							
						if(el){ 
							backup[randomId+"-"+i] = el.id;
							el.id = randomId+"-"+i;
						}						
					}
				}
			}
		}
		
	}

	load();
}
afApp.createAddon = function(filename, filetype, callback) {
	
	if(filename.indexOf('http://')!=-1)
	{
		filename = afApp.urlPrefix + filename;
	}
	
	if(!filetype)
	{
		var f = filename.split('.');
		filetype=f[f.length-1];
	}
	
	//console.log(filename+":"+filetype);
	if (filetype == "js") { // if filename is a external JavaScript file
		var fileref = document.createElement('script')
		fileref.setAttribute("type", "text/javascript")
		fileref.setAttribute("src", filename)
		GLOBAL_JS_VAR.push(filename);
	} else if (filetype == "css") { // if filename is an external CSS file
		var fileref = document.createElement("link")
		fileref.setAttribute("rel", "stylesheet")
		fileref.setAttribute("type", "text/css")
		fileref.setAttribute("href", filename)
		GLOBAL_CSS_VAR.push(filename);
	}
	
	if (typeof fileref != "undefined")
		document.getElementsByTagName("head")[0].appendChild(fileref)
		
	if (filetype == "js") { // if filename is a external JavaScript file
		fileref.onload = fileref.onreadystatechange = function() {
			if (!this.readyState || this.readyState == "loaded" || this.readyState == "complete") 
			{
				callback();
			}
		}
	} else if (filetype == "css") { // if filename is an external CSS file
		callback();
	}
	
}
afApp.widgetPopup = function(widget,title,superClass,winConfig,Application) {
	Application = Application?Application : App; //App is default application for all Appflower apps
	var viewport=Application.getViewport();
			
	if(!winConfig)
	{
		var winConfig = {};
		winConfig.width = 800;
		winConfig.height = 500;
	}
	else
	{
		winConfig=eval('({'+unescape(winConfig)+'});');
		
		winConfig.width = winConfig.width? winConfig.width:800;
		winConfig.height = winConfig.height? winConfig.height:500;
	}
	
	var getWidgetText = function(widget){
		if(widget.length > 45){
			return widget.substring(0,20)+"...."+widget.substring(widget.length-20,widget.length);
		}return widget;
	}
	
	//var mask = new Ext.LoadMask(Ext.get("body"), {msg: "<b>Opening widget</b> <br>Please Wait...",removeMask:true});	
	//mask.show();
	afApp.initLoadingProgress(viewport.layout.center.panel.getEl());
	var ajax = Ext.Ajax.request( {
		url : afApp.urlPrefix + widget,
		method : "GET",		
		success : function(r) {
			var json = Ext.util.JSON.decode(r.responseText);
			
			if(json.redirect&&json.message&&json.load)
			{
				//mask.hide();
				
				Ext.Msg.alert("Failure", json.message, function(){afApp.load(json.redirect,json.load);});
			}
			else
			{			
				var total_addons = new Array();
				
				if(json.addons && json.addons.js)
				{
					for ( var i = 0; i < json.addons.js.length; i++) {
						var addon = json.addons.js[i];
						if(!in_array(addon,GLOBAL_JS_VAR)){
							if(addon != null)
							total_addons.push(addon);			
						}
					}
				}
				if(json.addons && json.addons.css)
				{
					for ( var i = 0; i < json.addons.css.length; i++) {
						var addon = json.addons.css[i];
						if(!in_array(addon,GLOBAL_CSS_VAR)){
							if(addon != null)
							total_addons.push(addon);
						}
					}
				}
				afApp.executeAddons(total_addons,json,title,superClass,winConfig,Application);		
			}
			//mask.hide();
		},
		params : {
			widget_popup_request : true
		}
	});
}

// <a/> tags with widgetLoad CSS class will be loaded inside the center panel.
afApp.attachHrefWidgetLoad = (function ()
{
	var listener = function(e) {
		e.stopEvent();
		
		var el = Ext.get(e.getTarget());	

		var href = el.dom.href || el.dom.parentNode.href;
		 
		afApp.load(href);
	};

	return function() {
		//remove the listener before adding, because it might add the same listener multiple times
		var internalUrls = Ext.select('a.widgetLoad');
		internalUrls.un('click', listener);
		internalUrls.on('click', listener);
	};
})();
afApp.initLoadingProgress = function(el){	
	el.mask();
	var pb = Ext.getCmp("progress-bar");	
	var pbEl = Ext.get('progress-bar-el');
	if(!pbEl){	
		pbEl = Ext.DomHelper.append(el,{tag:'div',id:'progress-bar-el',style:'z-index:1000;position:absolute;top:40%;left:40%;width:20%'});
	}else{
		pbEl = Ext.get('progress-bar-el');
	}
	if(!pb){
		pb = new Ext.ProgressBar({id:'progress-bar',text:"Loading.... Please wait....."});
		pb.render(pbEl);
	}else{
		pb.updateProgress(0,"Loading.... Please wait.....");
		pb.show();
	}	
}
afApp.loadingProgress = function(el,percent){	
	var pb = Ext.getCmp("progress-bar");	
	pb.updateProgress(percent,Math.ceil(percent*100)+"% complete...");
	if(!pb.isVisible()) pb.show();
	if(percent >= 1) {el.unmask();setTimeout(function(){pb.hide();},500)}
}

afApp.executeAddonsLoadCenterWidget = function(viewport,addons,json,mask){
	var pb;
	var counter = 0;
	var finish;
	var load = function(){	
		if(counter >= addons.length){
			finish();
			return;
		}
		
		if(!Ext.getCmp("progress-bar")){
			pb = new Ext.ProgressBar();		
		}else{
			pb = Ext.getCmp('progress-bar');
		}
		//mask = new Ext.LoadMask(viewport.layout.center.panel.getEl(), {msg: "<b>Loading additional addons.....</b> <br>Please wait..<br>"+(counter+1)+" of "+addons.length+" addon(s) are loaded.<span id='progress-bar'></span>",removeMask:true});
		//mask.show();
		afApp.loadingProgress(viewport.layout.center.panel.getEl(),(counter+1)/addons.length);
		var nextAddon=addons[counter++];
			
		afApp.createAddon(nextAddon,false,load);
	};

	finish = function(){
		eval(json.source);				
		
		var panel = viewport.layout.center.panel;
		panel.add(eval(json.center_panel_first));
		
		//radu: remove the first added div after addition, workaround for extjs 3.3.0
		var divs = Ext.query('div[id=center_panel_first]');
		if(divs[0]){Ext.fly(divs[0]).remove();}
		
		//if (window.console) { console.time('doLayout'); }
		panel.doLayout();
		//if (window.console) { console.timeEnd('doLayout'); }
		afApp.loadingProgress(viewport.layout.center.panel.getEl(),1);	
		mask.hide();
	};
	
	load();
}
afApp.loadCenterWidget = function(widget) {
	
	widget = widget.replace(document.location.protocol+'//'+document.location.host+afApp.urlPrefix,'');
	var uri=widget.split('#');
	uri[0]=uri[0] || '/';
		
	afApp.currentCenterWidget = uri[0];
	afApp.observable.fireEvent('beforeload', uri[0]);
	
	var futureTab=uri[1]?'#'+uri[1]:'';
	var viewport=App.getViewport();
	var mask = new Ext.LoadMask(viewport.layout.center.panel.getEl(), {msg: "<b>Loading</b> <br>Please Wait...",removeMask:true});
	//mask.show();
	afApp.initLoadingProgress(viewport.layout.center.panel.getEl());
	var ajax = Ext.Ajax.request( {
		url : afApp.urlPrefix+uri[0],
		method : "GET",		
		success : function(r) {
			var json = Ext.util.JSON.decode(r.responseText);
			json.load = json.load?json.load:'center';
			json.title = json.title?json.title:'...';
			//hash contains the value without #in front of the internal link
			var futureHash=uri[0]+futureTab;
			var currentHash=document.location.href.replace(document.location.protocol+'//'+document.location.host+'/#','');			
			if(json.success === false) {
				mask.hide();
				Ext.Msg.alert('Failure', json.message);
				return;
			}

			if(json.redirect)
			{
				afApp.loadingProgress(viewport.layout.center.panel.getEl(),1);
				mask.hide();
												
				if(json.message)
				{
					Ext.Msg.alert(json.title, json.message, function(){
						afApp.load(json.redirect,json.load);
					});
				}
				else
				{
					afApp.load(json.redirect,json.load);
				}
			}
			else
			{				
				var total_addons = new Array();
				
				if(json.addons && json.addons.js)
				{
					for ( var i = 0; i < json.addons.js.length; i++) {
						var addon = json.addons.js[i];
						if(!in_array(addon,GLOBAL_JS_VAR)){
							if(addon != null)
							total_addons.push(addon);	
						}
					}
				}
				if(json.addons && json.addons.css)
				{
					for ( var i = 0; i < json.addons.css.length; i++) {
						var addon = json.addons.css[i];
						if(!in_array(addon,GLOBAL_CSS_VAR)){
							if(addon != null)
							total_addons.push(addon);
						}
					}
				}
										
				//adding a referer param to all Ajax request in Ext objects
				Ext.Ajax.extraParams = Ext.Ajax.extraParams || {};
				Ext.Ajax.extraParams['af_referer'] = futureHash;
				
				afApp.executeAddonsLoadCenterWidget(viewport,total_addons,json,mask);	
			}				
			
			if(json.executeAfter)
			{
				eval(json.executeAfter);
			}
		},
		failure : function(response) {
			mask.hide();
			var msg =  'Unable to load the content: ' +
				response.status + ' ' + response.statusText;
			Ext.Msg.alert('Failure', msg);
		},
		params : {
			widget_load : true
		}
	});
}
afApp.logTime = function (msg) {
	var today=new Date();
	
	var day=today.getDate();
	var month = today.getMonth();
	var year = today.getFullYear();
	var h=today.getHours();
	var m=today.getMinutes();
	var s=today.getSeconds();
	
	if(console)console.log('[',msg,']',day,'/',month,'/',year,' ',h,':',m,':',s,' | ',today.getTime());
}
/*
* reloads the grids data inside a portal page
*/
afApp.reloadGridsData = function (idXmls)
{	
	var portals=new Array();
	
	var center_panel_first_portal=Ext.getCmp('center_panel_first_portal');
	if(center_panel_first_portal)
	{
		if(center_panel_first_portal.layoutType=='NORMAL')
		{
			portals[0]=center_panel_first_portal;
		}
		else if(center_panel_first_portal.layoutType=='TABBED')
		{
			for(var i = 0; i < center_panel_first_portal.items.items.length; i++) {
				portals[i]=center_panel_first_portal.items.items[i].items.items[0];
			}
		}
		
		for(var i=0; i<portals.length; i++)
		{
			var col;
	        for(var c = 0; c < portals[i].items.getCount(); c++) {
	            col = portals[i].items.get(c); 	            
	            if(col.items) {
	                for(var s = 0; s < col.items.getCount(); s++) {
	                	var widget=col.items.get(s);
	                	if(idXmls.in_array(widget.idxml)&&widget.getEl()&&widget.getXType().toLowerCase().indexOf('grid')!=-1)
	                	{
	                		widget.store.reload();
	                	}
	                }
	            }
	        }
		}
	}
}
/**
* page/widget loader
*/
afApp.load = function (location, load, target, winProp)
{	
	if(location=='/false'||!location)return false;
	
	load = load || 'center';
	target = target || '_self';
	winProp = winProp || null;
	if(winProp && winProp.isPopup && !winProp.forceRedirect){ return false;}	
	if(target!='_self')
	{
		load='page';
	}
	
	if(location!='')
	{
		switch(load)
		{
			case "page":
				window.open(location,target,winProp);
				break;
			case "center":
				location=location.replace(document.location.protocol+'//'+document.location.host,'');
												
				//Ext History, also loads center widget if last loken is different from current one
				if(Ext.History.getToken()!=location)
				{
					Ext.History.add(location);
				}
				else
				{
					afApp.loadCenterWidget(location);
				}
			    break;
		}
	}
}
afApp.loadPopupHelp = function(widget) {
	
	var viewport=App.getViewport();
	var mask = new Ext.LoadMask(viewport.layout.center.panel.getEl(), {msg: "<b>Loading help</b> <br>Please Wait...",removeMask:true});
	mask.show();
	var ajax = Ext.Ajax.request( {
		url : afApp.urlPrefix + '/appFlower/popupHelp?idXml='+widget,
		method : "GET",		
		success : function(r) {
			var json = Ext.util.JSON.decode(r.responseText);
			
			if(json.redirect&&json.message)
			{
				mask.hide();
				
				Ext.Msg.alert("Failure", json.message, function(){window.location.href=json.redirect;});
			}
			else
			{			
				Ext.applyIf(json.winConfig, {
					autoScroll : true,
					maximizable : true,
					draggable:true,					
					closeAction:'hide',
					html : json.html
				});
				
				var win = new Ext.Window( json.winConfig );
				
				win.on("show",function(win){var pos = win.getPosition(); if(pos[1]<0) win.setPosition(pos[0],0);});
				
				win.doLayout()
				win.show();				
				
				win.on("move",function(win,x,y){
					if(y<0) win.setPosition(x,0);
					if(x < 100-win.getWidth()) win.setPosition(100-win.getWidth(),y);
					if(x > Ext.getBody().getWidth()-100) win.setPosition(Ext.getBody().getWidth()-100,y);
					if(y > Ext.getBody().getHeight()-100) win.setPosition(x,Ext.getBody().getHeight()-100);
				});
				
				mask.hide();
			}				
		}
	});
}
afApp.changeTabHash = function(tab)
{
	var uri=document.location.href.split('#');
	uri[1]=uri[1] || '/';
	uri[2]=tab.slug;
	
	var futureHash=uri[1]+'#'+uri[2];

	//Ext History, also loads center widget if last loken is different from current one
	if(Ext.History.getToken()!=futureHash)
	{
    	Ext.History.add(futureHash);
	}
		
	//adding a referer param to all Ajax request in Ext objects
	Ext.Ajax.extraParams = Ext.Ajax.extraParams || {};
	Ext.Ajax.extraParams['af_referer'] = futureHash;
}
/**
* load first request made to browser directly
*/
afApp.loadFirst = function()
{
	var uri=document.location.href.split('#');
	uri[1]=uri[1] || '/';
	uri[2]=uri[2]?'#'+uri[2]:'';
	
	var firstUri=uri[1]+uri[2];
	
	//load the toolbar inside the north panel, after rendering of layout, because of QuickTips bug
	var north = App.getNorth();
	var toolbar = App.getToolbar();

	north.add(toolbar);
	north.doLayout();
	
	afApp.loadCenterWidget(firstUri);
}

Ext.onReady(function(){

	afApp.attachHrefWidgetLoad();

});
//Ext History
Ext.History.on('change', function(token){
	//do not load the center widget if we are changing tabs
	if(token)
	{
		var tokenS=token.split('#');
		
		if(afApp.currentCenterWidget!=tokenS[0])
		{
			afApp.loadCenterWidget(token);
		}
		//this means that the center contains tabs
		else if(tokenS[1]){
			var viewport=App.getViewport();
			var tabPanel=viewport.layout.center.panel.items.items[0].items.items[0];
			if(tabPanel.getXType()=='tabpanel')
			{
				new Portals().onTabChange(tabPanel, tokenS);
			}
		}
	}
});
//used for triggering loading of latest content for some west panel item
afApp.loadWestWidget = function(widget)
{
	var viewport=App.getViewport();
	var westItems=viewport.layout.west.items;
	var panelItems=viewport.layout.west.panel.items.items;
	
	for(var i=0;i<westItems.length;i++){
		if(westItems[i].id == widget)
		{
			var westItem=westItems[i];
			var panelItem=panelItems[i];

			if(westItem.loadClass&&westItem.loadMethod)
			{
				var mask = new Ext.LoadMask(panelItem.getEl(), {msg: "<b>Loading</b> <br>Please Wait...",removeMask:true});
				mask.show();
				var ajax = Ext.Ajax.request( {
					url : afApp.urlPrefix + '/appFlower/loadWestContent',
					method : "POST",		
					success : function(r) {
						var response = Ext.util.JSON.decode(r.responseText);
						
						if(response.title)
						panelItem.setTitle(response.title);
						
						if(response.html)
						panelItem.body.dom.innerHTML=response.html;
						
						mask.hide();
					},
					params : {
						loadClass : westItem.loadClass,
						loadMethod : westItem.loadMethod
					}
				});
			}
		}
	}
}
//used to set/get current widget in center content
afApp.currentCenterWidget = false;
afApp.observable = new Ext.util.Observable();
