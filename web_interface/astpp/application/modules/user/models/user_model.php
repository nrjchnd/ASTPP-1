<?php

###############################################################################
# ASTPP - Open Source VoIP Billing Solution
#
# Copyright (C) 2016 iNextrix Technologies Pvt. Ltd.
# Samir Doshi <samir.doshi@inextrix.com>
# ASTPP Version 3.0 and above
# License https://www.gnu.org/licenses/agpl-3.0.html
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
###############################################################################

class user_model extends CI_Model {

	function user_model() {
		parent::__construct();
		if(Common_model::$global_config['system_config']['opensips']==0){
			$db_config = Common_model::$global_config['system_config'];
			$opensipdsn = "mysql://" . $db_config['opensips_dbuser'] . ":" . $db_config['opensips_dbpass'] . "@" . $db_config['opensips_dbhost'] . "/" . $db_config['opensips_dbname'] . "?char_set=utf8&dbcollat=utf8_general_ci&cache_on=true&cachedir=";
			$this->opensips_db = $this->load->database($opensipdsn, true);
		}
	}

	function validate_password($pass, $id) {
		$this->db->select('password');
		$this->db->where('number', $id);
		$query = $this->db->get('accounts');
		$count = $query->num_rows();
		return $count;
	}

	function update_password($newpass, $id) {
		$this->db->update('password', $newpass);
		$this->db->where('number', $id);
		$result = $this->db->get('accounts');
		return $result->result();
	}

	function change_password($id) {
		$this->db->select('password');
		$this->db->where('id', $id);
		$query = $this->db->get('accounts');
		$result = $query->result();
		return $result;
	}

	function change_db_password($update, $id) {
		$this->db->where('id', $id);
		$this->db->update('accounts', array('password' => $update));
	}

	function edit_account($accountinfo, $edit_id) {
		unset($accountinfo['action']);
		$this->db->where('id', $edit_id);
		$result = $this->db->update('accounts', $accountinfo);
		return true;
	}

	function get_user_packages_list($flag, $start = 0, $limit = 0) {
		$this->db_model->build_search('package_list_search');
		$account_data = $this->session->userdata("accountinfo");
		$where = array("pricelist_id" => $account_data['pricelist_id']);
		if ($flag) {
			$query = $this->db_model->getSelect("*", "packages", $where, "id", "ASC", $limit, $start);
		} else {
			$query = $this->db_model->countQuery("*", "packages", $where);
		}
		return $query;
	}

	function get_user_invoices_list($flag, $start = 0, $limit = 0) {
		$this->db_model->build_search('user_invoice_list_search');
		$accountinfo = $this->session->userdata('accountinfo');
		$reseller_id = $accountinfo['id'];
		$this->db->where('accountid', $reseller_id);
		if ($flag) {
			$this->db->select('*');
		} else {
			$this->db->select('count(id) as count');
		}
		if ($flag) {
		   if (isset($_GET['sortname']) && $_GET['sortname'] != 'undefined'){
			$this->db->order_by($_GET['sortname'], ($_GET['sortorder']=='undefined')?'desc':$_GET['sortorder']);
		   }else{
			$this->db->order_by('invoice_date', 'desc');
		   }
		}
		$result = $this->db->get('invoices');
		if ($result->num_rows() > 0) {
			if ($flag) {
				return $result;
			} else {
				$result = $result->result_array();
				return $result[0]['count'];
			}
		} else {
			if ($flag) {
				$query = (object) array('num_rows' => 0);
			} else {
				$query = 0;
			}
			return $query;
		}
	}

	function get_user_charge_history($flag, $start = 0, $limit = 0) {
		$this->db_model->build_search('user_charge_history_search');
		$accountinfo=$this->session->userdata('accountinfo');
		$where['accountid']=$accountinfo['id'];
		if($this->session->userdata('advance_search') != 1){
			 $where['created_date >=']=gmdate("Y-m-1 00:00:00");
			 $where['created_date <=']=gmdate("Y-m-d 23:59:59");
		}
	$where['item_type <>']='STANDARD';
		if ($flag) {
			$query = $this->db_model->select("*", "invoice_details", $where, "id", "DESC", $limit, $start);
		} else {
			$query = $this->db_model->countQuery("*", "invoice_details", $where);
		}
		return $query;
	}
    
	function get_user_refill_list($flag, $start = '', $limit = '') {
		$this->db_model->build_search('user_refill_report_search');
		$accountinfo = $this->session->userdata['accountinfo'];
		$where = array("accountid" => $accountinfo["id"]);
		if ($flag) {
			$query = $this->db_model->select("*", "payments", $where, "payment_date", "DESC", $limit, $start);
		} else {
			$query = $this->db_model->countQuery("*", "payments", $where);
		}
		return $query;
	}

	function get_user_emails_list($flag, $start = 0, $limit = 0) {
		$account_data = $this->session->userdata("accountinfo");
		$this->db_model->build_search('user_emails_search');
        
		$where = array('accountid' => $account_data['id']);
		if ($flag) {
			$query = $this->db_model->select("*", "mail_details", $where, "id", "DESC", $limit, $start);
		} else {
			$query = $this->db_model->countQuery("*", "mail_details", $where);
		}
		return $query;
	}

	function add_invoice_config($add_array) {
		$result = $this->db->insert('invoice_conf', $add_array);
		return true;
	}

	function edit_invoice_config($add_array, $edit_id) {
		$this->db->where('id', $edit_id);
		$result = $this->db->update('invoice_conf', $add_array);
		return true;
	}

	function edit_alert_threshold($add_array, $edit_id) {
		$this->db->where('id', $edit_id);
		$result = $this->db->update('accounts', $add_array);
		return true;
	}
    
	function user_ipmap_list($flag,$limit='',$start=''){
		$accountinfo=$this->session->userdata('accountinfo');
		$where['accountid']=$accountinfo['id'];
		$this->db_model->build_search('user_ipmap_search');
		if ($flag) {
		 $query = $this->db_model->select("*", "ip_map", $where, "id", "ASC", $limit, $start);
		} else {
		 $query = $this->db_model->countQuery("*", "ip_map", $where);
		}
		  return $query;
	}
	function user_sipdevices_list($flag, $accountid = "",$start = "", $limit = "") {
		$where = array("accountid" => $accountid);
		$this->db_model->build_search('user_sipdevices_search');
		$query = array();
		if ($flag) {
		$deviceinfo = $this->db_model->select("*", "sip_devices", $where, "id", "ASC", $limit, $start);
		if ($deviceinfo->num_rows > 0) {
			$add_array = $deviceinfo->result_array();
			foreach ($add_array as $key => $value) {
				$vars = json_decode($value['dir_vars']);
				$vars_new = json_decode($value['dir_params'], true);
				$passowrds = json_decode($value['dir_params']);
				$query[] = array('id' => $value['id'],
						'username' => $value['username'],
						'accountid' => $value['accountid'],
						'status' => $value['status'],
						'effective_caller_id_name' => $vars->effective_caller_id_name,
						'voicemail_enabled' => $vars_new['vm-enabled'],
						'voicemail_password' => $vars_new['vm-password'],
						'voicemail_mail_to' => $vars_new['vm-mailto'],
						'voicemail_attach_file' => $vars_new['vm-attach-file'],
						'vm_keep_local_after_email' => $vars_new['vm-keep-local-after-email'],
						'effective_caller_id_number' => $vars->effective_caller_id_number,
						'password' => $passowrds->password,
						'creation_date'=>gmdate('Y-m-d H:i:s'),
						'last_modified_date'=>gmdate('Y-m-d H:i:s')
				);
			}
		}
	} else {
		$query = $this->db_model->countQuery("*", "sip_devices", $where);
	}
	return $query;
	}
    
	function user_sipdevice_info($edit_id){
		$sipdevice_info = $this->db_model->getSelect("*", "sip_devices", array('id' => $edit_id));
		$sipdevice_arr = (array)$sipdevice_info->first_row();
		$vars = (array)json_decode($sipdevice_arr['dir_vars']);
		$params = (array)json_decode($sipdevice_arr['dir_params'],true);
		$query = array('id' => $sipdevice_arr['id'],
						'fs_username' => $sipdevice_arr['username'],
						'accountcode' => $sipdevice_arr['accountid'],
						'status' => $sipdevice_arr['status'],
						'effective_caller_id_name' => $vars['effective_caller_id_name'],
						'effective_caller_id_number' => $vars['effective_caller_id_number'],
						'voicemail_enabled' => $params['vm-enabled'],
						'voicemail_password' => $params['vm-password'],
						'voicemail_mail_to' => $params['vm-mailto'],
						'voicemail_attach_file' => $params['vm-attach-file'],
						'vm_keep_local_after_email' => $params['vm-keep-local-after-email'],
						'vm_send_all_message' => $params['vm-email-all-messages'],
						'fs_password' => $params['password']);
		return $query;
	}
	function user_sipdevice_add($add_array){
		$account_data = $this->session->userdata("accountinfo");
	$parms_array = array('password' => $add_array['fs_password'],
			'vm-enabled' => $add_array['voicemail_enabled'],
			'vm-password' => $add_array['voicemail_password'],
			'vm-mailto' => $add_array['voicemail_mail_to'],
			'vm-attach-file' => $add_array['voicemail_attach_file'],
			'vm-keep-local-after-email' => $add_array['vm_keep_local_after_email'],
			'vm-email-all-messages' => $add_array['vm_send_all_message']);
			$add_array['status'] = isset($add_array['status']) ? $add_array['status'] : "0";
			$parms_array_vars = array('effective_caller_id_name' => $add_array['effective_caller_id_name'],
			'effective_caller_id_number' => $add_array['effective_caller_id_number'],
			'user_context' => 'default');

	$new_array = array('creation_date'=>gmdate('Y-m-d H:i:s'),
						   'username' => $add_array['fs_username'],
						   'accountid' => $account_data['id'],
						   'status' => $add_array['status'],
						   'dir_params' => json_encode($parms_array),
						   'dir_vars' => json_encode($parms_array_vars),
							'sip_profile_id' => $this->common->get_field_name('id','sip_profiles',array('name'=>'default')));    
	$this->db->insert('sip_devices', $new_array);
	$this->common->mail_to_users('add_sip_device', $account_data);
	return true;
	}
	function user_sipdevice_edit($add_array){
	$parms_array = array('password' => $add_array['fs_password'],
			'vm-enabled' => $add_array['voicemail_enabled'],
			'vm-password' => $add_array['voicemail_password'],
			'vm-mailto' => $add_array['voicemail_mail_to'],
			'vm-attach-file' => $add_array['voicemail_attach_file'],
			'vm-keep-local-after-email' => $add_array['vm_keep_local_after_email'],
			'vm-email-all-messages' => $add_array['vm_send_all_message']
			);
	$parms_array_vars = array('effective_caller_id_name' => $add_array['effective_caller_id_name'],
	'effective_caller_id_number' => $add_array['effective_caller_id_number'],);
	$log_type = $this->session->userdata("logintype");
	if($log_type == 0  || $log_type == 3 || $log_type == 1){
		$add_array['sip_profile_id']=$this->common->get_field_name('id','sip_profiles',array('name'=>'default'));
	}
	$add_array['status'] = isset($add_array['status'])?$add_array['status']:"0";
	$new_array = array('last_modified_date'=>gmdate('Y-m-d H:i:s'),'username' => $add_array['fs_username'],'status' => $add_array['status'],
	 	 	   'dir_params' => json_encode($parms_array),
			   'dir_vars' => json_encode($parms_array_vars), 'sip_profile_id' => $add_array['sip_profile_id']);		   
	$this->db->update('sip_devices', $new_array,array('id'=>$add_array['id']));
	return true;
	}
	function getuser_cdrs_list($flag, $start, $limit,$export=true) {
		$this->db_model->build_search('user_cdrs_report_search');
		$account_data = $this->session->userdata("accountinfo");
		$field_name='debit';
		if($account_data['type']==0 || $account_data['type']==1){
			$where['accountid']=$account_data['id'];
            
		}
		if($account_data['type']==3){
		   $where['provider_id']=$account_data['id'];
		   $field_name='cost';
		}
		$table_name=$account_data['type'] !=1 ? 'cdrs': 'reseller_cdrs';
		if($this->session->userdata('advance_search') != 1){
		$where['callstart >= ']=date("Y-m-d")." 00:00:00";
			$where['callstart <=']=date("Y-m-d")." 23:59:59";
		}
		$this->db->where($where);
		$this->db->order_by("callstart desc");
		if ($flag) {
			if (!$export)
				$this->db->limit($limit, $start);
			$this->db->select('callstart,callerid,callednum,pattern,notes,billseconds,disposition,debit,cost,accountid,pricelist_id,calltype,is_recording,trunk_id,uniqueid');
		}else {
			$this->db->select('count(*) as count,sum(billseconds) as billseconds,sum(debit) as total_debit,sum(cost) as total_cost,group_concat(distinct(pricelist_id)) as pricelist_ids,group_concat(distinct(trunk_id)) as trunk_ids');
		}
		$result = $this->db->get($table_name);
		return $result;
	}
    
	function user_fund_transfer($data){
	$accountinfo = $this->session->userdata['accountinfo'];
		$data["payment_by"] = $accountinfo['reseller_id'] > 0 ? $accountinfo['reseller_id'] : -1 ;
		$data['accountid'] = $data['id'];
		$data['payment_mode'] = $data['payment_type'];
		unset($data['action'],$data['id'],$data['account_currency'],$data['payment_type']);
		if (isset($data)) {
			$data['credit']=$data['credit'] =='' ?  0 : $data['credit'];
			$date = gmdate('Y-m-d H:i:s');
			$accountid=$data['accountid'];
			while($accountid > 0 ){
				$customer_id=$accountid;
				$accountid=$this->common_model->get_parent_info($accountid);
				$parent_id=$accountid > 0 ? $accountid : -1;
				$balance = $this->db_model->update_balance($data['credit'], $customer_id,$data['payment_mode']);
				if($data['payment_mode'] == 0){
					$insert_arr = array("accountid" => $customer_id,
					"credit" => $data['credit'],
					'payment_mode'=>$data['payment_mode'],
					'type'=>"SYSTEM",
					"notes" => $data['notes'],
					"payment_date" => $date, 
					'payment_by'=>$parent_id,
					);
			return $this->db->insert("payments", $insert_arr);
				}
			}
		}
	}
	 function user_dashboard_recent_recharge_info()
	{
	$accountinfo=$this->session->userdata('accountinfo');
	$userlevel_logintype=$this->session->userdata('userlevel_logintype');
	
	$where_arr=array('payment_by'=>-1);
	if($userlevel_logintype == 1){
	  $where_arr=array('payment_by'=>$accountinfo['id']);
	}
	if($userlevel_logintype == 0 || $userlevel_logintype == 3){
	  $where_arr=array('accountid'=>$accountinfo['id']);
	}
		$this->db->where($where_arr);
		$this->db->select('id,accountid,credit,payment_date,notes');
		$this->db->from('payments');
		$this->db->limit(10);
		$this->db->order_by('payment_date','desc');
	return $this->db->get();
	}
    
	function get_user_rates_list($flag, $start = 0, $limit = 0) {
		$this->db_model->build_search('user_rates_list_search');
		$account_data = $this->session->userdata("accountinfo");
		$where = array("pricelist_id" => $account_data["pricelist_id"],"status" => '0');
		if ($flag) {
			$query = $this->db_model->select("*", "routes", $where, "id", "ASC", $limit, $start);
		} else {
			$query = $this->db_model->countQuery("*", "routes", $where);
		}
		return $query;
	}
	function get_user_opensips($flag, $account_number = "", $start = "0", $limit = "0") {
		$this->db_model->build_search_opensips($this->opensips_db,'user_opensips_search');	
		$this->opensips_db->where('accountcode',$account_number);
		if ($flag) {
		  $this->opensips_db->limit($limit,$start);
		}
		$result = $this->opensips_db->get("subscriber");
		if($result->num_rows() > 0){
	  if($flag){
		return $result;
	  }
	  else{
		return $result->num_rows();
	  }
		}else{
		 if($flag){
		  $result=(object)array('num_rows'=>0);
	  }
	  else{
		  $result=0;
	  }
	  return $result;
		}
	}
	function user_opensips_add($data) {
		unset($data["action"]);
		$data['creation_date']=gmdate("Y-m-d H:i:s");
		$accountinfo=$this->session->userdata('accountinfo');
	$data['reseller_id']=$accountinfo['type']==1 ? $accountinfo['id'] : 0;
		$this->opensips_db->insert("subscriber", $data);
	}

	function user_opensips_edit($data, $id) {
	  unset($data["action"]);
	  $data=array("username"=>$data['username'],"password"=>$data['password'],"accountcode"=>$data['accountcode'],"domain"=>$data['domain']);
	  $this->opensips_db->where("id", $id);
	  $data['last_modified_date']=gmdate("Y-m-d H:i:s");
	  $this->opensips_db->update("subscriber", $data);
	}
	function user_opensips_delete($id) {
		$this->opensips_db->where("id", $id);
		$this->opensips_db->delete("subscriber");
		return true;
	}
	function get_user_invoice_list($flag, $start = 0, $limit = 0){
	$this->db_model->build_search('user_invoice_list_search');
	$accountinfo=$this->session->userdata('accountinfo');
	$where = array("accountid" => $accountinfo['id'],'confirm'=>1);	
	$this->db->where($where);
	$or_where= "(type='I' OR type='R')";
	$this->db->where($or_where);
		if ($flag) {
			$query = $this->db_model->select("*", "invoices", "", "invoice_date", "desc", $limit, $start);
            
		} else {
			$query = $this->db_model->countQuery("*", "invoices", "");
		}
      
		return $query;
	}
	function get_user_cdrs_info($flag,$accountid,$start = 0, $limit = 0){
	 if ($flag) {
			$query = $this->db_model->select("*", "reseller_cdrs", "", "invoice_date", "desc", $limit, $start);
            
		} else {
			$query = $this->db_model->countQuery("*", "invoices", "");
		}
      
	}
	function get_invoiceconf($accountid) {
		$return_array=array();
 	$logintype = $this->session->userdata('logintype');
		if ($logintype == 1 || $logintype == 5) {
          
		$where = array("accountid" => $this->session->userdata["accountinfo"]['id']);
		}else{
	   		 $where=array('id'=> $accountid);
	}      
		$query = $this->db_model->getSelect("*","invoice_conf",$where);
		foreach($query->result_array() as $key => $value)
		{
			$return_array=$value;
		}       
		 return $return_array;
	}
    
    
	function getprovider_cdrs_list($flag, $start, $limit,$export=true) {
		$this->db_model->build_search('user_provider_cdrs_report_search');
		$account_data = $this->session->userdata("accountinfo");
		$where['provider_id']=$account_data['id'];
		if($this->session->userdata('advance_search') != 1){
			$where['callstart >= ']=date("Y-m-d")." 00:00:00";
			$where['callstart <=']=date("Y-m-d")." 23:59:59";
		}
		$this->db->where($where);
		$this->db->order_by("callstart","desc");
		if ($flag) {
			if (!$export)
				$this->db->limit($limit, $start);
			$this->db->select('callstart,callerid,callednum,pattern,notes,billseconds,disposition,cost,accountid,pricelist_id,calltype,is_recording,trunk_id,uniqueid');
		}else {
			$this->db->select('count(*) as count,sum(billseconds) as billseconds,sum(cost) as total_cost,group_concat(distinct(trunk_id)) as trunk_ids');
		}
		$result = $this->db->get('cdrs');
		return $result;
	}
     
}

?>
