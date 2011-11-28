<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');

require_once('config.php');

$plugin_info = array(
	'pi_name' => 'Structure Monthly Archives',
	'pi_version' => '1.1',
	'pi_author' => 'Sean Delaney',
	'pi_author_url' => 'http://www.seandelaney.co.uk',
	'pi_description' => 'Creates a monthly archive list based on Structure pages by passing an Entry ID.',
	'pi_usage' => structure_monthly_archives::usage()
);

/**
 * Structure Monthly Archives Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			Sean Delaney <seanogodubhshlaine@me.com>
 * @copyright		Copyright (c) 2011
 */

class Structure_monthly_archives {	
	private $EE;
	private $sma_return_data;
	
	/**
     * Class Constructor
     *
     * @access public
     * @return null
     */
	public function __construct() { 
		$this->EE =& get_instance();		
	} /* END Constructor */
	
	public function show() {
		// Get tag parameters
		$sma_parent_entry_id = $this->EE->TMPL->fetch_param('parent_entry_id');
		$sma_url_path = $this->EE->TMPL->fetch_param('url_path');
		$sma_css_class = $this->EE->TMPL->fetch_param('css_class');
		$sma_css_id = $this->EE->TMPL->fetch_param('css_id');
		$sma_listing = $this->EE->TMPL->fetch_param('listing');
		
		$sma_archive_entries = $sma_entry_ids = array();
		
		// Check for required tag parameters
		if(!empty($sma_parent_entry_id) && !empty($sma_url_path)) :
			if($sma_listing == true) :
				// We want to get all the entry ids for a parent id from the Structure Listing table.
				$sma_results = $this->EE->db->query('SELECT entry_id FROM '.$this->EE->db->dbprefix('structure_listings').' WHERE parent_id = '.$sma_parent_entry_id);
			else :
				// We want to get all the entry ids for a parent id from the Structure table.
				$sma_results = $this->EE->db->query('SELECT entry_id FROM '.$this->EE->db->dbprefix('structure').' WHERE parent_id = '.$sma_parent_entry_id);
			endif;
		
			// If there are no records found, then we do nothing - as we can't!
			if($sma_results->num_rows() > 0) :
			    
			    // Loop over each record and build a entry ids array
			    foreach($sma_results->result_array() as $sma_row) :
			    	array_push($sma_entry_ids,$sma_row['entry_id']);
			    endforeach;
			
				// Probably not required, but I also do checks like this… Check if sma_entry_ids array contains values
				if(count($sma_entry_ids) > 0) :
					foreach($sma_entry_ids as $sma_entry_id) :
						// For each entry id we want to get the year and month of when it was published.
						$sma_results = $this->EE->db->query('SELECT year, month FROM '.$this->EE->db->dbprefix('channel_titles').' WHERE entry_id = '.$sma_entry_id.' LIMIT 1');
						
						if($sma_results->num_rows() > 0) :
							// Loop over each record found and build our archive entries
							foreach($sma_results->result_array() as $sma_row) :
								// We have to keep track of the total entries for a specific year/month combo
								
								// Add entry to archives entries list by default
								$sma_insert = true;
								
								// Loop over all archive entries and compare against current year/month combo. 
								foreach($sma_archive_entries as &$sma_archive_entry) :
									if($sma_archive_entry['year'] == $sma_row['year'] && $sma_archive_entry['month'] == $sma_row['month']) :
										
										// Current year/month combo already exists in the archives entries array so we just increase the total count.
										$sma_archive_entry['total']++;
										
										// Prevent duplicates
										$sma_insert = false;
									endif;
								endforeach;
								
								// If true, we know the current year/month combo isnt in the archives entries list yet.
								if(true == $sma_insert) :
									array_push($sma_archive_entries,array('year' => $sma_row['year'],'month' => $sma_row['month'],'total' => 1));
								endif;
							endforeach;	
						endif;	
					endforeach;
				endif;
			
				// Sort em' in reverse.
				arsort($sma_archive_entries);
			
				// Now we build up our ordered list. Some developers would probably use uno-ordered lists here but we are actually using an ordered list since we sorted them…
				$this->sma_return_data = '<ol '.((!empty($sma_css_class)) ? 'class="'.$sma_css_class.'"' : '').' '.((!empty($sma_css_id)) ? 'id="'.$sma_css_id.'"' : '').'>'."\r\n";
				
				foreach($sma_archive_entries as $sma_archive_entry) :
					
					// Build our url paths and the appearence of list.
					$this->sma_return_data .= '<li><a href="/'.$sma_url_path.'/'.$sma_archive_entry['year'].'/'.$sma_archive_entry['month'].'/">'.date('F Y',mktime(0,0,0,$sma_archive_entry['month'],1,$sma_archive_entry['year'])).'</a> ('.$sma_archive_entry['total'].')</li>'."\r\n";
				endforeach;
				
				$this->sma_return_data .= '</ol>'."\r\n";
			endif;
		endif;
		
		// remove double slashes if any and return our archived entries list.
		return str_replace('//','/',$this->sma_return_data);
	}
	
	/**
	 * Usage
	 *
	 * This function describes how the plugin is used.
	 *
	 * @access	public
	 * @return	string
	 */
  	function usage() {
  		ob_start(); 
  		?>
  		Usage:
		
		=============================
		The Tag
		=============================
		
		{exp:structure_monthly_archives:show}
		
		==============
		TAG PARAMETERS
		==============

		parent_entry_id=
		The Entry ID must be a channel entry that uses Structure and status set to open
		[REQUIRED]
		
		url_path=
		The url path of where your archives template lives.  This can be a /template_group/template/ combo.
		
		Example: /news-events/archives/2011/10/  Also see tag examples below.
		[REQUIRED]
		
		listing=
		Set true if you wish to use a Listing
		[OPTIONAL]
		
		css_class=
		Allows you to set a CSS Class
		[OPTIONAL]
		
		css_id=
		Allows you to set a CSS ID
		[OPTIONAL]
		
		==============
		TAG EXAMPLES
		==============
		
		{exp:structure_monthly_archives:show parent_entry_id="3" url_path="/news-events/archives/" listing="true" css_class="news_events_archives" css_id="news_events_archives"}
		<?php
  		$buffer = ob_get_contents();
	
  		ob_end_clean(); 

  		return $buffer;
  	}
}

/* End of file pi.structure_monthly_archives.php */ 
/* Location: ./system/expressionengine/third_party/structure_monthly_archives/pi.structure_monthly_archives.php */
?>