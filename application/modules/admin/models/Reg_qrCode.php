<?php
  /**
   *
   */
  class Reg extends CI_Model
  {

    function __construct(){
			$this->load->database();
		}

    public function get() {
      $query = $this->db->get('tb_reg');
      return $query->result_array();
    }

    public function count_all() {
      return $this->db->count_all('tb_reg');
    }

    public function get_pdf($id) {
      $query = $this->db->get_where('v_e-ticket' , array('id_reg' => $id));
      return $query->row_array();
    }

    public function get_id($id) {
      $query = $this->db->get_where('tb_reg' , array('id_reg' => $id));
      return $query->row_array();
    }

    public function insert() {

      $data = array(
              'id_usr'      => $this->input->post('idusr', TRUE),
              'id_event'    => $this->input->post('idevnt', TRUE),
              'id_jen_reg'  => $this->input->post('jenreg', TRUE)
      );
      $r1     = $this->db->get_where('tb_reg', $data);

      if ($r1->num_rows() == 1) {
        $idevn = $this->input->post('idevnt');
        $jen   = $this->input->post('jenreg');
        return $mssg = "<SCRIPT LANGUAGE='JavaScript'>
              window.alert('ID user yang Anda masukan telah terdaftar pada event ".$idevn." sebagai ".$jen."')
              window.location.href='".base_url()."Admin#A_content/transaksi/reg';
              </SCRIPT>";
      } else {
        // Cek User
        $data2 = array(
                'id_usr' => $this->input->post('idusr', TRUE)
        );
        $r2     = $this->db->get_where('tb_usr', $data2);

        // Cek Event
        $data3 = array(
                'id_event' => $this->input->post('idevnt', TRUE)
        );
        $r3     = $this->db->get_where('tb_event', $data3);

        if ($r2->num_rows() == 1 && $r3->num_rows() == 1) {
          // Cek Quota
          $ide      = $this->input->post('idevnt');

          $this->db->like('id_event', $ide);
          $this->db->like('stat_byr', '1');
          $this->db->from('v_bayar');
          $reg_count = $this->db->count_all_results();

          foreach ($r3->result_array() as $eqty) {
            $qty      = $eqty['quota'];
            $bts_byr  = $eqty['batas_bayar'];
            $jdl      = $eqty['judul_event'];
            $deadline = $eqty['tgl_akhir_reg'];
          }
          $rqty       = $qty - $reg_count;
          $cdate      = date ("Y-m-d");
          $pay_date   = date('Y-m-d', strtotime('+'.$bts_byr.' days', strtotime($cdate)));

          if ($cdate > $deadline) {
            return $mssg = "<SCRIPT LANGUAGE='JavaScript'>
                  window.alert('Maaf tanggal terakhir registrasi pada '".$deadline.")
                  window.location.href='".base_url()."Admin#A_content/transaksi/reg';
                  </SCRIPT>";
          } else {

            if ($rqty == '0') {
              return $mssg = "<SCRIPT LANGUAGE='JavaScript'>
                    window.alert('Maaf Quota Peserta untuk event ".$jdl." sudah habis')
                    window.location.href='".base_url()."Admin#A_content/transaksi/reg';
                    </SCRIPT>";
            } else {
              // Library
              $this->load->library('ciqrcode');
        			$this->load->helper('url');

              $idevn = $this->input->post('idevnt');
              $noreg = $this->input->post('idreg');
              $idreg = $idevn.$noreg;

              $qrnm               = "qr_code_".time().".png";
              //$qr['data']         = base_url().'admin/qr/eticket/'.$idreg;
              $qr['data']         = $idreg;
        			$qr['savename']     = 'assets/file_upload/qr_code/eticket/'.$qrnm;
        			$this->ciqrcode->generate($qr);

              $this->form_validation->set_rules('idusr','UID','required');
              $this->form_validation->set_rules('idevnt','EID','required');
              $this->form_validation->set_rules('jenreg','Jen Reg','required');
              $this->form_validation->set_rules('rdate','Tanggal','required');

              if ($this->form_validation->run() == FALSE) {
                return $mssg = "<SCRIPT LANGUAGE='JavaScript'>
                      window.alert('Pastikan Semua data telah terisi ')
                      window.location.href='".base_url()."Admin#A_content/transaksi/reg';
                      </SCRIPT>";
              } else {
                $data = array(
                  'id_reg'      => $idreg,
                  'id_usr'      => $this->input->post('idusr'),
                  'tgl_reg'     => $this->input->post('rdate'),
                  'id_event'    => $this->input->post('idevnt'),
                  'id_jen_reg'  => $this->input->post('jenreg'),
                  'qr_code'     => $qrnm,
                  'pay_date'    => $pay_date,
                  'status'      => "0"
                );

                $this->db->insert('tb_reg' , $data);
                return $mssg = '1';
              }
            }
          }
        } else {
          return $mssg = "<SCRIPT LANGUAGE='JavaScript'>
                window.alert('ID user atau ID Event yang Anda masukan salah atau tidak valid')
                window.location.href='".base_url()."Admin#A_content/transaksi/reg';
                </SCRIPT>";
        }
      }
    }

    public function update($id) {

      $data = array(
        'id_reg'      => $id,
        'id_usr'      => $this->input->post('idusr'),
        'tgl_reg'     => $this->input->post('rdate'),
        'id_event'    => $this->input->post('idevnt'),
        'status'      => NULL
      );

      $this->db->where('id_reg', $id);
      $this->db->update('tb_reg' , $data);
      return $mssg = '1';
    }

    public function update_stat($id) {

      $data = array(
        'status'      => "2"
      );

      $this->db->where('id_reg', $id);
      $this->db->update('tb_reg' , $data);
      return $mssg = '1';
    }

    public function delete($id) {
      $query      = $this->db->get_where('tb_reg' , array('id_reg' => $id));
      $data       = $query->row_array();
      $pic        = $data['qr_code'];
      $path       = "assets/file_upload/qr_code/eticket/";
      unlink($path.$pic);
      return $this->db->delete('tb_reg' , array('id_reg' => $id));
    }

    public function auto_id_reg() {
      $q = $this->db->query("SELECT MAX(RIGHT(id_reg,4)) AS idmax FROM tb_reg");
      $id = ""; //kode awal
      if($q->num_rows()>0){ //jika data ada
        foreach($q->result() as $k){
          $tmp = ((int)$k->idmax)+1; //string kode diset ke integer dan ditambahkan 1 dari kode terakhir
          $id = sprintf("%04s", $tmp); //kode ambil 4 karakter terakhir
        }
      } else{ //jika data kosong diset ke kode awal
        $id = "0001";
      }

      $r = $id;
      return $r;
    }
  }
?>
