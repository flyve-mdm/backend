---
layout: post
code: true
wiki: false
published: true
title: Plugin Flyve MDM Package
permalink: development/devdocs/pluginflyvemdmpackage
---

* Class name: PluginFlyvemdmPackage
* Namespace: 
* Parent class: CommonDBTM





Properties
----------


### $rightname

    public string $rightname = 'flyvemdm:package'





* Visibility: **public**
* This property is **static**.


### $usenotepad

    protected boolean $usenotepad = true





* Visibility: **protected**


### $usenotepadRights

    protected boolean $usenotepadRights = true





* Visibility: **protected**


Methods
-------


### getTypeName

    mixed PluginFlyvemdmPackage::getTypeName($nb)

Localized name of the type



* Visibility: **public**
* This method is **static**.


#### Arguments
* $nb **mixed** - &lt;p&gt;integer  number of item in the type (default 0)&lt;/p&gt;



### getMenuPicture

    string PluginFlyvemdmPackage::getMenuPicture()

Returns the picture file for the menu



* Visibility: **public**
* This method is **static**.




### defineTabs

    mixed PluginFlyvemdmPackage::defineTabs($options)





* Visibility: **public**


#### Arguments
* $options **mixed**



### getTabNameForItem

    string PluginFlyvemdmPackage::getTabNameForItem(\CommonGLPI $item, \number $withtemplate)

Returns the tab name of this itemtype, depending on the itemtype on which it will be displayed
If the tab shall not display then returns an empty string



* Visibility: **public**


#### Arguments
* $item **CommonGLPI** - &lt;p&gt;on which the tab will show&lt;/p&gt;
* $withtemplate **number** - &lt;p&gt;template mode for $item : 0 = no template - 1 = edit template - 2 = from template&lt;/p&gt;



### displayTabContentForItem

    mixed PluginFlyvemdmPackage::displayTabContentForItem(\CommonGLPI $item, \number $tabnum, \number $withtemplate)

Display the content of the tab provided by this itemtype



* Visibility: **public**
* This method is **static**.


#### Arguments
* $item **CommonGLPI**
* $tabnum **number**
* $withtemplate **number**



### showForm

    mixed PluginFlyvemdmPackage::showForm(integer $ID, array $options)

Display a form to view, create or edit



* Visibility: **public**


#### Arguments
* $ID **integer** - &lt;p&gt;ID of the item to show&lt;/p&gt;
* $options **array**



### getMaxFileSize

    integer PluginFlyvemdmPackage::getMaxFileSize()

Gets the maximum file size allowed for uploads from PHP configuration



* Visibility: **protected**
* This method is **static**.




### addNeededInfoToInput

    mixed PluginFlyvemdmPackage::addNeededInfoToInput($input)





* Visibility: **public**


#### Arguments
* $input **mixed**



### prepareInputForAdd

    mixed PluginFlyvemdmPackage::prepareInputForAdd($input)





* Visibility: **public**


#### Arguments
* $input **mixed**



### prepareInputForUpdate

    mixed PluginFlyvemdmPackage::prepareInputForUpdate($input)

(non-PHPdoc)



* Visibility: **public**


#### Arguments
* $input **mixed**



### post_getFromDB

    mixed PluginFlyvemdmPackage::post_getFromDB()

Actions done after the getFromDB method



* Visibility: **public**




### post_updateItem

    mixed PluginFlyvemdmPackage::post_updateItem($history)





* Visibility: **public**


#### Arguments
* $history **mixed**



### pre_deleteItem

    mixed PluginFlyvemdmPackage::pre_deleteItem()





* Visibility: **public**




### post_purgeItem

    mixed PluginFlyvemdmPackage::post_purgeItem()





* Visibility: **public**




### createEntityDirectory

    mixed PluginFlyvemdmPackage::createEntityDirectory(string $dir)

Create a directory



* Visibility: **protected**


#### Arguments
* $dir **string**



### getSearchOptions

    mixed PluginFlyvemdmPackage::getSearchOptions()





* Visibility: **public**




### getFileURL

    boolean|string PluginFlyvemdmPackage::getFileURL()

Get the download URL for the application



* Visibility: **public**




### sendFile

    mixed PluginFlyvemdmPackage::sendFile()

Sends a file



* Visibility: **protected**




### hook_entity_purge

    mixed PluginFlyvemdmPackage::hook_entity_purge(\CommonDBTM $item)

Deletes the packages related to the entity



* Visibility: **public**


#### Arguments
* $item **CommonDBTM**


