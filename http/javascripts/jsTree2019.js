/***************************************************************
# $Id: jsTree.js 6673 2010-08-02 13:52:19Z christoph $
*  http://www.mapbender.org/index.php/Mod_treefolder2.php
*
*  Copyright notice
*  (c) 2003-2004 Tobias Bender (tobias@phpXplorer.org)
*  All rights reserved
*
*  This script is part of the jsTree project. The jsTree project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

var jst_cm
var jst_cmT
var jst_activeNode
var jst_reload_strData = ""
var jst_reload_ctlImage
var jst_reload_halt = false
var jst_any_expanded
var jst_expandAll_int
var jst_loaded = false
var jst_state_paths = new Array()

var jst_delimiter = ["|", "<|>"]
var jst_id = "jsTree"
var jst_container = "document.body"
var jst_data = "arrNodes"
var jst_expandAll_warning = "Expanding all nodes can take a while depending on your hardware! Continue?"
var jst_target
var jst_context_menu
var jst_display_root = false
var jst_highlight = false
var jst_highlight_color = "white"
var jst_highlight_bg = "navy"
var jst_highlight_padding = "1px"
var jst_image_folder = "./images"
var jst_reloading = false
var jst_reload_frame = "reLoader"
var jst_reload_script = "tree_jsTree_reload.php"
var jst_reloading_status = "loading tree nodes ..."

function absTop(nd){
	return nd.offsetParent ? nd.offsetTop + absTop(nd.offsetParent) : nd.offsetTop
}

function nodeClick(nd){
	if(jst_highlight){
		if(jst_activeNode){
			jst_activeNode.style.color = ""
			jst_activeNode.style.backgroundColor = ""
			nd.style.padding = ""
		}
		nd.style.color = jst_highlight_color
		nd.style.backgroundColor = jst_highlight_bg
		nd.style.padding = jst_highlight_padding
		jst_activeNode = nd
	}
//	if(childExists(nd.parentNode.parentNode))
//		window.scrollTo(0, absTop(nd) - 5)
}

function _getDefinition(data, depth){

	var d = new Array()

	if(!data)
		return ""

	var sD = ""
	for(var i = 0; i < depth; i++)
		sD += '\t'

	if(data != eval(jst_data))
		d.push(",")

	d.push("\n" + sD + "[")

	var nodes = new Array()

	for(var n1 in data){

		var infos = new Array()

		for(var i = 0; i < 5; i++)
			infos.push(data[n1][1][i] ? "'" + data[n1][1][i].replace(/\n/g, '\\' + 'n') + "'" : null)

		infos.push(null);
		
		if(data[n1][1][6]){
			infos.push("'"+data[n1][1][6]+"'");
//TODO checked and disabled state
		}else{
			infos.push(null);
		}

		for(var i = 6; i > 0; i--)
			if(!infos[i]){
				infos.pop()
			}else{
				break
			}

		nodes.push("\n" + sD + "\t['" + data[n1][0].replace(/\'/g, '\\' + "'") + "', [" + infos.join(",") + "]" + _getDefinition(data[n1][2], depth + 1) + "]")
	}
	
	d.push(nodes.join(",") + "\n" + sD + "]")

	return d.join("")
}

function getDefinition(){
	return jst_data + "=" + _getDefinition(eval(jst_data), 0)
}

function getDomNode(path){
	var parts = path.split(jst_delimiter[0]);
	var tr = document.getElementById(parts[parts.length-1]);	
	if(tr)
		return tr;
	var tBody = get1stTBody()
	for(var p = 0; p < parts.length; p++){
		for(var c = 0; c < tBody.childNodes.length; c++){
			var tr = tBody.childNodes[c]
			if(tr.id == parts[p]){
				if(p == parts.length - 1){
					return tr
				}else{
					if(!childExists(tr))
						return null;
					tBody = tBody.childNodes[c + 1].childNodes[1].firstChild.firstChild

					if(!tBody)
						return null
				}
				break
			}
		}
	}
	return null
}

function delArrItem(a, p){
	var b = a.slice(0, p)
	var e = a.slice(p + 1)
	return b.concat(e)
}
function addArrItem(a, p, v){
	var b = a.slice(0, p)
	var e=a.slice(p)
	b[b.length] = v
	return b.concat(e)
}

function _editDataNode(action, path, nd){
	var ps = jst_data
	var parts = path.split(jst_delimiter[0])

	for(var p = 0; p < parts.length; p++){
		var arrData = eval(ps)
		
	  for(var d = 0; d < arrData.length; d++)
			if(parts[p] == arrData[d][0]){

				if(p == parts.length - 1){

					switch(action){
						case "d":
							if(ps != jst_data)
								eval(ps + "=delArrItem(" + ps + "," + d + ")")
						break;
						case "a":
							if(!eval(ps)[d][2])
								eval(ps)[d].push(new Array())
							eval(ps)[d][2].push(nd)
						break;
						case "u":
							if(!eval(ps)[d][2])
								eval(ps)[d].push(new Array())
							eval(ps)[d][2].unshift(nd)
						break;
					}
					return true
					
					
				}else{
					ps = ps + "[" + d + "][2]"
				}
				break

			}
	}
	return false
}

function addNode(path, nd, sel, refresh, begining){
	if(_editDataNode((begining?"u":"a"), path, nd)){
		if(refresh){	
			rebuildNode(path, true)
			rebuildNode(path)
		}

		if(sel)
			nodeClick(getDomNode(path + jst_delimiter[0] + nd[0]).childNodes[1].childNodes[1])
	}
}


function deleteNode(path){
	if(_editDataNode("d", path))
		rebuildNode(path, true)
}

function _getState(tBody, path){
	var hasSub = false
	
	for(var c = 0; tBody!=null  && c < tBody.childNodes.length; c++){
		var tr = tBody.childNodes[c]
		if(childExists(tr) && isExpanded(tr)){
			_getState(tBody.childNodes[c + 1].childNodes[1].firstChild.firstChild, path + (path != "" ? jst_delimiter[0] : "") + tr.id)
			hasSub = true
		}
	}
	if(!hasSub)
		jst_state_paths.push(path)
}

function getState(){
	jst_state_paths = new Array()
	_getState(get1stTBody(), "")
	return jst_state_paths.join(jst_delimiter[1])
}

function setState(data){
	jst_state_paths = data.split(jst_delimiter[1])
	for(var p in jst_state_paths){
		var path="";
		var pathElements = jst_state_paths[p].split(jst_delimiter[0]);
		for(i in pathElements){
			path += pathElements[i];
			var tr = getDomNode(path)
			
			if(tr){
				var f1 = tr.firstChild
				if(f1){
					var f2 = f1.firstChild
					if(!isExpanded(tr) && f2)
						if(f2.onclick)
							f2.onclick()
				}
			}
			path+=jst_delimiter[0];
		}
	}
}

function rebuildNode(path, parent){
	if(parent){
		var arrPath = path.split(jst_delimiter[0])
		arrPath.pop()
		path = arrPath.join(jst_delimiter[0])
	}
	
	if(path.split(jst_delimiter[0]).length<=1){
		renderTree()
	}else{
	
		var nd = getDomNode(path)
	
		if(nd){
			var nn = nd.nextSibling

			if(nn){
				var nCh = nn.childNodes[1].firstChild
				if(nCh.nodeName == "TABLE")
					nd.parentNode.parentNode.deleteRow(nn.rowIndex)
			}
			if(nd.firstChild.firstChild.onclick)
				nd.firstChild.firstChild.onclick()
		}
	}
}

function setNodeColor(path, color){
	var nd = getDomNode(path)
	if(nd){
		nd.childNodes[1].childNodes[nd.childNodes[1].childNodes.length-1].style.color=color;
	}
}

function setNodeImage(path, Img){
	var ps = jst_data;
	var parts = path.split(jst_delimiter[0])
	var arrData = null;

	var nd = getDomNode(path);
	if(nd){
		var cb = nd.childNodes[1].childNodes[0];
		if(cb && cb.nodeName=="IMG"){
			if(Img)
				cb.src=jst_image_folder+"/"+Img;
			else if(childExists(nd)){
				var s = nd.nextSibling.style
				if(s.display == ""){
					cb.src = jst_image_folder + "/verticaldots.svg";
				}else{
					cb.src = jst_image_folder + "/verticaldots.svg";
				}
			}
		}
	}
	return true
}

function selectNode(path){
	var nd = getDomNode(path)
	if(nd){
		nodeClick(nd.childNodes[1].childNodes[nd.childNodes[1].childNodes.length-1])
		return true
	}else{
		return false
	}
}

function IsChecked(path, ctrlNr){
	var nd = getDomNode(path)
	if(nd){
		var fc = nd.childNodes[1].firstChild;
		if(fc && fc.nodeName=="IMG")ctrlNr++;
		var cb = nd.childNodes[1].childNodes[ctrlNr];
		if(cb && cb.nodeName=="INPUT"){
			return cb.checked;
		}
	}
	return false;
}

//getChildrenCheckState returns:
//	1:if each child with checkbox ctrlNr is checked
//	0:if none is checked
// -1:if states are different or ctrlNr is no checkbox or any other error
function getChildrenCheckState(path, ctrlNr){
	var state = -1;
	var plus=0;
	var nd = getDomNode(path);

	if(!nd)return -1;
	if(!childExists(nd))return -1;
	var tBody = nd.nextSibling.childNodes[1].firstChild.firstChild;
	if(!tBody)return -1;
	for(var i = 0;i < tBody.childNodes.length; i++){
		var fc = tBody.childNodes[i].childNodes[1].firstChild;
		plus=0;
		if(fc && fc.nodeName=="IMG")plus++;
		var cb = tBody.childNodes[i].childNodes[1].childNodes[ctrlNr+plus];
		if(cb && cb.nodeName=="INPUT"){
			if(state == -1) 
				state = cb.checked?1:0;
			else{
				if(state != cb.checked?1:0)
					return -1;
			}
			
		}		
	}
	return state;
}

function checkChildren(path, ctrlNr, bChk){
	var nd = getDomNode(path);
	var plus;
	
	if(!nd)return false;
	if(!childExists(nd))return false;
	var tBody = nd.nextSibling.childNodes[1].firstChild.firstChild;
	if(!tBody)return false;
	for(var i = 0;i < tBody.childNodes.length; i++){
		var fc = tBody.childNodes[i].childNodes[1].firstChild;
		plus=0;
		if(fc && fc.nodeName=="IMG")plus++;
		var cb = tBody.childNodes[i].childNodes[1].childNodes[ctrlNr+plus];
		if(cb && cb.nodeName=="INPUT"&&!cb.disabled){
			cb.checked = bChk;	
			if(cb.onclick){cb.onclick();}
		}		
	}
	return true;
}

function checkNode(path, ctrlNr, bChk, triggerOnclick){
	var nd = getDomNode(path)
	if(typeof(triggerOnclick)=='undefined')
		triggerOnclick = true;
	if(nd){
		var fc = nd.childNodes[1].firstChild;
		if(fc && fc.nodeName=="IMG")ctrlNr++;
		var cb = nd.childNodes[1].childNodes[ctrlNr];
		if(cb && cb.nodeName=="INPUT"){
			cb.checked = bChk;
			if(cb.onclick&&triggerOnclick)cb.onclick();
			return true;
		}
	}
	return false;
}

function enableCheckbox(path, ctrlNr, pEnabled){
	var nd = getDomNode(path)
	if(nd){
		var fc = nd.childNodes[1].firstChild;
		if(fc && fc.nodeName=="IMG")ctrlNr++;
		var cb = nd.childNodes[1].childNodes[ctrlNr];
		if(cb && cb.nodeName=="INPUT"){
			cb.disabled = !pEnabled;
			return true;
		}
	}
	return false;	
}



function get1stTBody(){
	return eval(jst_container).firstChild.firstChild.childNodes[1].childNodes[1].firstChild.firstChild
}

function __switchAll(tBody, expand){
	if(!tBody)
		return false

	for(var c = 0; c < tBody.childNodes.length; c++){
		var tr = tBody.childNodes[c]
		var img = tr.firstChild.firstChild

		if(img)
			if(img.onclick){
				if((expand && !childExists(tr)) || ((expand && !isExpanded(tr)) || (!expand && isExpanded(tr)))){
					if(img.id != "rootImage")
						img.onclick()
						
					jst_any_expanded = true
				}
				
				if(tBody.childNodes[c + 1])
					__switchAll(tBody.childNodes[c + 1].childNodes[1].firstChild.firstChild, expand)
			}
	}
}

function _switchAll(expand){
	if(jst_reload_halt)
		return

	__switchAll(get1stTBody(), expand)
	
	if(jst_reloading){
		if(!jst_any_expanded)
			cancelExpandAll()

		jst_any_expanded = null
	}
}

function expandAll(){
	if(jst_expandAll_warning ? confirm(jst_expandAll_warning) : true)	
		if(jst_reloading){
			jst_expandAll_int = window.setInterval("if(!jst_reload_halt)_switchAll(true)", 100)
		}else{
			_switchAll(true)
		}
}

function cancelExpandAll(){
	if(jst_expandAll_int)
		window.clearInterval(jst_expandAll_int)
}

function closeAll(){
	_switchAll(false)
}

function isExpanded(tr){
	return childExists(tr) ? tr.nextSibling.style.display != "none" : false
}

function childExists(tr){
	var n = tr.nextSibling;
	if(!n||n.childNodes.length<2)return false;
	n=n.childNodes[1].firstChild;
	if(!n)return false;
	return n.nodeName == "TABLE";
}

function getPath(strData){
	if(strData.indexOf("[") > 0){
		
		var sub3 = strData.substr(0, strData.lastIndexOf("["))
		var sub6 = sub3.substr(0, sub3.lastIndexOf("["))
		
		return (getPath(sub6) != "" ? getPath(sub6) + jst_delimiter[0] : "") + eval(sub3 + "[0]")
	}else{
		return ""
	}
}

function reloadCallback(){

	eval(jst_reload_strData + "=window.frames['" + jst_reload_frame + "']." + jst_data)
	
	renderNode(jst_reload_strData, jst_reload_ctlImage, null, true)
	
	window.status = ""
	
	jst_reload_halt = false
	jst_reload_strData = ""
	jst_reload_ctlImage = null
}

function renderNode(strData, ctlImg, event, reload){

	if(event)
		event.cancelBubble = true

	if(jst_reload_halt && !reload)
		return

	jst_loaded = false

	if(jst_reloading && !reload && eval(strData).length == 0){
		jst_reload_strData = strData
		jst_reload_ctlImage = ctlImg
		jst_reload_halt = true
		if(jst_reloading_status)
			window.status = jst_reloading_status

		window.frames[jst_reload_frame].document.location.href = "./" + jst_reload_script + (jst_reload_script.indexOf("?") > -1 ? "&" : "?") + "path=" + getPath(strData)
		return
	}

	var tr = ctlImg.parentNode.parentNode

	if(ctlImg.id != "rootFolder"){
		var fldImg = tr.childNodes[1].firstChild

		if(childExists(tr)){
			var s = tr.nextSibling.style
			var img1 = jst_image_folder + "/" + (tr.nextSibling.nextSibling ? "" : "")

			if(s.display == ""){
				s.display = "none"
				ctlImg.src = img1 + "roundplus.svg"
				if(String(fldImg.src).indexOf("expanded")!=-1)
					fldImg.src = jst_image_folder + "/verticaldots.svg";
			}else{
				s.display = ""
				ctlImg.src = img1 + "roundminus.svg"
				if(String(fldImg.src).indexOf("closed")!=-1)
					fldImg.src = jst_image_folder + "/verticaldots.svg";
			}
			return
		}else{
			ctlImg.src = jst_image_folder + "/" + (tr.nextSibling ? "" : "") + "roundplus.svg"
			if(eval(strData.substr(0, strData.length-3)+"[1][3]"))
				fldImg.src = jst_image_folder + "/" + eval(strData.substr(0, strData.length-3)+"[1][3]");
			else
				fldImg.src = jst_image_folder + "/verticaldots.svg";
		}
	}

	var newTr = tr.parentNode.insertRow((tr.rowIndex?tr.rowIndex:0) + 1)

	if(ctlImg.id != "rootFolder"){
		newTr.style.display = "none";
	}

	newTr.appendChild(document.createElement('td'))
	newTr.appendChild(document.createElement('td'))
		
	if(newTr.nextSibling)
		newTr.firstChild.setAttribute("background", jst_image_folder + "/branch_empty.png", "false")
	
	newTr.childNodes[1].innerHTML = renderChildren(strData)
	
	var nodes = eval(strData)

	var ndWithChildren = 0;
	for(var n in nodes){
		var n0 = nodes[n]
		var n1 = n0[2]

		if(n1){
			renderNode(strData + "[" + n + "][2]" ,newTr.childNodes[1].firstChild.firstChild.childNodes[parseInt(n)+ndWithChildren].firstChild.firstChild);
			ndWithChildren++;
		}
	}
	
	jst_loaded = true
}

window.mbTreeDragSource = null;

window.mbTreeDragStart = function(e, strData) {
	window.mbTreeDragSource = strData;
	if (e && e.dataTransfer) {
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData('text/plain', strData);
	}
	var tr = (e && e.currentTarget) ? e.currentTarget : null;
	if (tr) {
		tr.classList.add('mb-dragging-row');
	}
};

window.mbTreeDragOver = function(e) {
	if (e && e.preventDefault) {
		e.preventDefault();
	}
	if (e && e.dataTransfer) {
		e.dataTransfer.dropEffect = 'move';
	}
	var tr = (e && e.currentTarget) ? e.currentTarget : null;
	if (!tr) return false;

	var rect = tr.getBoundingClientRect();
	var midY = rect.top + (rect.height / 2);
	if (e.clientY < midY) {
		tr.classList.add('mb-drag-over-above');
		tr.classList.remove('mb-drag-over-below');
	} else {
		tr.classList.add('mb-drag-over-below');
		tr.classList.remove('mb-drag-over-above');
	}
	return false;
};

window.mbTreeDragLeave = function(e) {
	var tr = (e && e.currentTarget) ? e.currentTarget : null;
	if (tr) {
		tr.classList.remove('mb-drag-over-above', 'mb-drag-over-below');
	}
};

window.mbTreeDragEnd = function(e) {
	var tr = (e && e.currentTarget) ? e.currentTarget : null;
	if (tr) {
		tr.classList.remove('mb-dragging-row', 'mb-drag-over-above', 'mb-drag-over-below');
	}
	var els = document.querySelectorAll('.mb-drag-over-above, .mb-drag-over-below, .mb-dragging-row');
	for (var i = 0; i < els.length; i++) {
		els[i].classList.remove('mb-drag-over-above', 'mb-drag-over-below', 'mb-dragging-row');
	}
};

window.mbTreeDrop = function(e, targetStrData) {
	if (e && e.preventDefault) e.preventDefault();
	if (e && e.stopPropagation) e.stopPropagation();

	var srcStr = window.mbTreeDragSource;
	window.mbTreeDragEnd(e);

	if (!srcStr || srcStr === targetStrData) {
		return false;
	}

	try {
		var srcNode = eval(srcStr);
		var targetNode = eval(targetStrData);

		if (!srcNode || !targetNode) return false;

		var srcIds = (srcNode[1] && srcNode[1][7]) ? srcNode[1][7] : null;
		var targetIds = (targetNode[1] && targetNode[1][7]) ? targetNode[1][7] : null;

		if (!srcIds || !targetIds) return false;

		var mapIdx = srcIds[0];
		if (typeof mb_mapObj === 'undefined' || !mb_mapObj[mapIdx]) return false;
		var mapObj = mb_mapObj[mapIdx];

		var getIdx = function(s) {
			var m = String(s).match(/\[(\d+)\]$/);
			return m ? parseInt(m[1], 10) : null;
		};

		var srcIdx = getIdx(srcStr);
		var targetIdx = getIdx(targetStrData);

		if (srcIdx === null || targetIdx === null) return false;

		var diff = targetIdx - srcIdx;
		if (diff === 0) return false;

		var isReverse = (typeof reverse !== 'undefined' && String(reverse) === 'true');
		var moveUp = isReverse ? (diff > 0) : (diff < 0);
		var steps = Math.abs(diff);

		var srcWmsIdx = srcIds[1];
		var srcWmsObj = mapObj.wms[srcWmsIdx];
		if (!srcWmsObj) return false;

		var srcWmsId = srcWmsObj.wms_id;
		var srcLayerIdx = srcIds[2];
		var srcLayerId = (srcWmsObj.objLayer && srcWmsObj.objLayer[srcLayerIdx]) ? srcWmsObj.objLayer[srcLayerIdx].layer_id : srcWmsObj.objLayer[0].layer_id;

		for (var s = 0; s < steps; s++) {
			mapObj.move(srcWmsId, srcLayerId, moveUp);
		}

		if (typeof getState === 'function') {
			treeState = getState();
		}

		if (typeof reloadTree === 'function') {
			reloadTree();
		}

		if (typeof setState === 'function' && typeof treeState !== 'undefined' && treeState) {
			setState(treeState);
		}

		if (typeof mod_treeGDE_map !== 'undefined' && typeof Mapbender !== 'undefined' && Mapbender.modules && Mapbender.modules[mod_treeGDE_map]) {
			Mapbender.modules[mod_treeGDE_map].setMapRequest();
		} else if (mapObj.setMapRequest) {
			mapObj.setMapRequest();
		}
	} catch (err) {
		console.log("Drag & drop reorder error: ", err);
	}
	return false;
};

function renderChildren(strData, tblCls, menu){

	var code = Array()

	code.push('<table style="border-collapse:collapse;" cellspacing="0" cellpadding="0" border="0" class="' + tblCls + '">')
	
	var nodes = eval(strData)

	for(var n in nodes){
		var n0 = nodes[n]
		var n1 = n0[2]

		code.push('<tr' + ((strData == jst_data && !jst_display_root) ? ' style="display:none;"' : '') + ' class="treeGDE3_tr" id="' + n0[0] + '" draggable="true" ondragstart="mbTreeDragStart(event, \'' + strData + '[' + n + ']\')" ondragover="mbTreeDragOver(event)" ondragleave="mbTreeDragLeave(event)" ondrop="mbTreeDrop(event, \'' + strData + '[' + n + ']\')" ondragend="mbTreeDragEnd(event)"><td><img' + (strData == jst_data ? ' style="display:none" id="rootImage"' : '') + ' src="' + jst_image_folder + '/')

		if(n1){
			code.push('roundplus.svg" onClick="renderNode(' + "'" + strData + "[" + n + "][2]" + "'" + ',this,event)" class="action"')
		}else{
			code.push('branch_empty.png"')
		}
		
		if(jst_context_menu && !n0[1][5] && !menu)
			n0[1][5] = jst_context_menu
	
		code.push(
			' alt=""></td><td nowrap>'+
			((n1||n0[1][3]) ? 
				'<img'  + (
					((strData == jst_data)&&!jst_display_root) ? 
						' style="display:none"' : ''
					) +  
				(n0[1][5] ? 
					' class="action" title="Einstellungen" onClick="showMenu(\'' + 
					strData + '[' + n + ']\', this, event)"' : ''
				) + 
				' src="' + jst_image_folder + '/' + 
				(n1 ? 
					(n0[1][3]?
						n0[1][3]:(n0[1][5] ? "gear.svg" : "closed_folder.png")
					) : n0[1][3] ? 
						n0[1][3] : "gear.svg"
				) + '" alt="">':''
			)+
			(
				(n0[1][6] && ((strData != jst_data)||jst_display_root)) ?
					n0[1][6]:''
			) + 
			(typeof n0[0] === "string" && n0[0].match(/folder_/) ? '<span class="node"' : '<a class="node"')  + 
//			'<a class="node"'  + 
			(
				((strData == jst_data)&&!jst_display_root) ? 
					' style="display:none"' : ''
			)+ 
			(n0[1][4] ? 
				' title="' + n0[1][4] + '"' : ''
			) + 
			(typeof n0[0] === "string" && n0[0].match(/folder_/) ? ' ' : ' onClick="nodeClick(this)" ')  + 			
			'href=' + "'" + 
			(menu ? 
				String(n0[1][1]).replace(/{@strData}/g, strData) : n0[1][1] 
			) + "'" + 
			(n0[1][2] ? 
				' target="' + n0[1][2] + '"' : jst_target ? 
					' target="' + jst_target + '"' : ''
			) + '>' + n0[1][0]  + 
			(typeof n0[0] === "string" && n0[0].match(/folder_/) ? '</span>' : '</a>') + 
//			'</a>' + 
			'</td></tr>'
		);
	}
	code.push('</table>')
	
	return code.join("")
}

if (typeof window.mbUpdateOpacitySlider === 'undefined') {
	window.mbUpdateOpacitySlider = function(mapObj_id, wms_id, val) {
		var labelEl = document.getElementById('mb_op_val_' + mapObj_id + '_' + wms_id);
		if (labelEl) {
			labelEl.innerHTML = val + '%';
		}
		if (typeof mb_mapObj !== 'undefined' && mb_mapObj[mapObj_id] && mb_mapObj[mapObj_id].wms && mb_mapObj[mapObj_id].wms[wms_id]) {
			var opacityPercent = 100 - parseInt(val, 10);
			if (isNaN(opacityPercent)) opacityPercent = 100;
			mb_mapObj[mapObj_id].wms[wms_id].setOpacity(opacityPercent);
		}
	};
	window.mbApplyOpacitySlider = function(mapObj_id, wms_id, val) {
		window.mbUpdateOpacitySlider(mapObj_id, wms_id, val);
		if (typeof Mapbender !== 'undefined' && Mapbender.modules && typeof mod_treeGDE_map !== 'undefined' && Mapbender.modules[mod_treeGDE_map]) {
			Mapbender.modules[mod_treeGDE_map].setMapRequest();
		}
	};
}

function renderSettingsPanel(strData) {
	var n0 = eval(strData);
	var title = (n0 && n0[1] && n0[1][0]) ? n0[1][0] : "Optionen";
	var menuItems = (n0 && n0[1] && n0[1][5]) ? n0[1][5] : [];
	var ids = (n0 && n0[1] && n0[1][7]) ? n0[1][7] : null;

	var code = [];
	code.push('<div class="mb-tree-settings-panel" onclick="if(event && event.stopPropagation) event.stopPropagation();">');
	
	// Header
	code.push('<div class="mb-tree-settings-header">');
	code.push('<div class="mb-tree-settings-title-container">');
	code.push('<img class="mb-tree-settings-icon" src="' + jst_image_folder + '/gear.svg" alt="" />');
	code.push('<span class="mb-tree-settings-title" title="' + String(title).replace(/"/g, '&quot;') + '">' + title + '</span>');
	code.push('</div>');
	code.push('<button class="mb-tree-settings-close" onclick="hideMenu();" title="Schließen">&times;</button>');
	code.push('</div>');

	// Check if opacity option exists in menuItems
	var hasOpacityOption = false;
	for (var i = 0; i < menuItems.length; i++) {
		if (menuItems[i][0] === 'menu_opacity_up' || menuItems[i][0] === 'menu_opacity_down') {
			hasOpacityOption = true;
			break;
		}
	}

	if (hasOpacityOption && ids && typeof mb_mapObj !== 'undefined' && mb_mapObj[ids[0]] && mb_mapObj[ids[0]].wms && mb_mapObj[ids[0]].wms[ids[1]]) {
		var wmsObj = mb_mapObj[ids[0]].wms[ids[1]];
		var opacityRaw = parseFloat(wmsObj.gui_wms_mapopacity);
		if (isNaN(opacityRaw)) opacityRaw = 1.0;
		var transparencyVal = 100 - Math.round(opacityRaw * 100);

		code.push('<div class="mb-tree-opacity-section">');
		code.push('<div class="mb-tree-opacity-label">');
		code.push('<span>Transparenz</span>');
		code.push('<span class="mb-tree-opacity-value" id="mb_op_val_' + ids[0] + '_' + ids[1] + '">' + transparencyVal + '%</span>');
		code.push('</div>');
		code.push('<input type="range" id="mb_slider_' + ids[0] + '_' + ids[1] + '" class="mb-opacity-slider" min="0" max="100" value="' + transparencyVal + '" ');
		code.push('oninput="mbUpdateOpacitySlider(' + ids[0] + ',' + ids[1] + ', this.value);" ');
		code.push('onchange="mbApplyOpacitySlider(' + ids[0] + ',' + ids[1] + ', this.value);" />');
		code.push('<div class="mb-tree-opacity-presets">');
		code.push('<button type="button" class="mb-opacity-preset-btn" onclick="var s=document.getElementById(\'mb_slider_' + ids[0] + '_' + ids[1] + '\'); if(s){s.value=0; mbApplyOpacitySlider(' + ids[0] + ',' + ids[1] + ', 0);}">0%</button>');
		code.push('<button type="button" class="mb-opacity-preset-btn" onclick="var s=document.getElementById(\'mb_slider_' + ids[0] + '_' + ids[1] + '\'); if(s){s.value=50; mbApplyOpacitySlider(' + ids[0] + ',' + ids[1] + ', 50);}">50%</button>');
		code.push('<button type="button" class="mb-opacity-preset-btn" onclick="var s=document.getElementById(\'mb_slider_' + ids[0] + '_' + ids[1] + '\'); if(s){s.value=100; mbApplyOpacitySlider(' + ids[0] + ',' + ids[1] + ', 100);}">100%</button>');
		code.push('</div>');
		code.push('</div>');
	}

	// Actions List
	code.push('<div class="mb-tree-settings-actions">');
	for (var i = 0; i < menuItems.length; i++) {
		var item = menuItems[i];
		var itemId = item[0];
		if (itemId === 'menu_opacity_up' || itemId === 'menu_opacity_down' || itemId === 'menu_hide' || itemId === 'menu_move_up' || itemId === 'menu_move_down') {
			continue;
		}

		var itemData = item[1];
		var label = itemData[0].replace(/&nbsp;/g, '').trim();
		var actionJs = String(itemData[1]).replace(/{@strData}/g, strData);
		var iconFile = itemData[3] ? itemData[3] : 'gear.svg';
		var isDelete = (itemId === 'menu_delete');

		var cleanJs = '';
		if (isDelete && ids) {
			cleanJs = 'remove_wms(' + ids[0] + ',' + ids[1] + ',' + (ids[2] || 0) + ');';
		} else {
			cleanJs = actionJs.replace(/^javascript:/i, '').replace(/"/g, '&quot;');
		}
		code.push('<button class="mb-tree-action-btn' + (isDelete ? ' mb-tree-action-delete' : '') + '" ');
		code.push('onclick="hideMenu(); ' + cleanJs + ';">');
		code.push('<img class="mb-tree-action-icon" src="' + jst_image_folder + '/' + iconFile + '" alt="" />');
		code.push('<span>' + label + '</span>');
		code.push('</button>');
	}
	code.push('</div>'); // end actions
	code.push('</div>'); // end panel

	return code.join("");
}

function showMenu(strData, img, event){
	if (!jst_cm) {
		jst_cm = document.getElementById("contextMenu");
	}

	var rect = (img && img.getBoundingClientRect) ? img.getBoundingClientRect() : null;
	var top = 100;
	var left = 100;
	
	if (rect) {
		top = rect.bottom + 4;
		left = rect.left;
	} else if (event) {
		top = event.clientY + 4;
		left = event.clientX;
	}

	var cardWidth = 250;
	var cardHeight = 220;
	
	if (left + cardWidth > window.innerWidth - 10) {
		left = Math.max(10, window.innerWidth - cardWidth - 10);
	}
	if (top + cardHeight > window.innerHeight - 10) {
		if (rect && rect.top - cardHeight > 10) {
			top = rect.top - cardHeight - 4;
		} else {
			top = Math.max(10, window.innerHeight - cardHeight - 10);
		}
	}

	jst_cm.style.position = "fixed";
	jst_cm.style.top = top + "px";
	jst_cm.style.left = left + "px";
	jst_cm.style.zIndex = "999999";
	jst_cm.innerHTML = renderSettingsPanel(strData);
	jst_cm.style.visibility = "visible";

	setTimeout(function() {
		document.body.addEventListener('click', hideMenu);
	}, 10);

	if (event) {
		if (event.stopPropagation) event.stopPropagation();
		event.cancelBubble = true;
	}
}

function hideMenu(){
	if (jst_cm) {
		jst_cm.style.visibility = "hidden";
	}
	document.body.removeEventListener('click', hideMenu);
}

function renderTree(){
	//	TestDate = new Date();TestStartZeit=TestDate.getTime();
	eval(jst_container).innerHTML = '<table cellspacing="0" cellpadding="0" border="0"><tr id="'+eval(jst_data + "[0][0]")+'"><td colspan="2"><span id="rootFolder"></span></td></tr></table><div style="position:absolute;top:-100;left:-100" id="contextMenu"></div>'
	renderNode(jst_data, document.getElementById("rootFolder"))
	renderNode(jst_data + "[0][2]", document.getElementById("rootImage"))
	
	jst_cm = document.getElementById("contextMenu")
	jst_loaded = true
	//	TestDate=new Date();TestStopZeit=TestDate.getTime();alert(TestStopZeit-TestStartZeit);
}

