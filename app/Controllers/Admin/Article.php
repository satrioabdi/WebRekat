<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PostsModel;

class Article extends BaseController
{
    function __construct()
    {
        $this->validation = \Config\Services::validation();
        $this->m_posts = new PostsModel();
        helper("global_fungsi_helper");
        /** untuk konfigurasi internal */
        $this->halaman_controller = "article";
        $this->halaman_label = "Artikel";
    }
    function index()
    {
        $data = [];
        if ($this->request->getVar('aksi') == 'hapus' && $this->request->getVar('post_id')) {
            $dataPost = $this->m_posts->getPost($this->request->getVar('post_id'));
            if ($dataPost['post_id']) { #memastikan bahwa ada data
                @unlink(LOKASI_UPLOAD . "/" . $dataPost['post_thumbnail']);
                $aksi = $this->m_posts->deletePost($this->request->getVar('post_id'));
                if ($aksi == true) {
                    session()->setFlashdata('success', "Data berhasil dihapus");
                } else {
                    session()->setFlashdata('warning', ['Gagal menghapus data']);
                }
            }
            return redirect()->to("admin/" . $this->halaman_controller);
        }
        $data['templateJudul'] = "Halaman " . $this->halaman_label;

        $post_type = $this->halaman_controller;
        $jumlahBaris = 3;
        $katakunci = $this->request->getVar('katakunci');
        $group_dataset = "dt";

        $hasil = $this->m_posts->listPost($post_type, $jumlahBaris, $katakunci, $group_dataset);

        $data['record'] = $hasil['record'];
        $data['pager'] = $hasil['pager'];
        $data['katakunci'] = $katakunci;

        $currentPage = $this->request->getVar('page_dt');
        $data['nomor'] = nomor($currentPage, $jumlahBaris);

        /** header */
        echo view('admin/v_template_header', $data);
        echo view('admin/v_article', $data);
        echo view('admin/v_template_footer', $data);
        /** footer */
    }

    function tambah()
    {
        $data = [];
        if ($this->request->getMethod() == 'post') {
            $data = $this->request->getVar(); #setiap yang diinputkan akan dikembalikan ke view
            $aturan = [
                'post_title' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Judul harus diisi'
                    ],
                ],
                'post_content' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Konten harus diisi'
                    ],
                ],
                'post_thumbnail' => [
                    'rules' => 'is_image[post_thumbnail]',
                    'errors' => [
                        'is_image' => 'Hanya gambar yang diperbolehkan untuk diupload'
                    ]
                ]
            ];
            $file = $this->request->getFile('post_thumbnail');
            if (!$this->validate($aturan)) {
                session()->setFlashdata('warning', $this->validation->getErrors());
            } else {
                $post_thumbnail = '';
                if ($file->getName()) {
                    $post_thumbnail = $file->getRandomName();
                }
                $record = [
                    'username' => session()->get('akun_username'),
                    'post_title' => $this->request->getVar('post_title'),
                    'post_status' => $this->request->getVar('post_status'),
                    'post_thumbnail' => $post_thumbnail,
                    'post_description' => $this->request->getVar('post_description'),
                    'post_content' => $this->request->getVar('post_content')
                ];
                $post_type = $this->halaman_controller;
                $aksi = $this->m_posts->insertPost($record, $post_type);
                if ($aksi != false) {
                    $page_id = $aksi;
                    if ($file->getName()) {
                        $lokasi_direktori = LOKASI_UPLOAD; #diambil dari constants
                        $file->move($lokasi_direktori, $post_thumbnail);
                    }
                    session()->setFlashdata('success', 'Data berhasil dimasukkan');
                    return redirect()->to('admin/' . $this->halaman_controller . '/edit/' . $page_id);
                } else {
                    session()->setFlashdata('warning', ['Gagal memasukkan data']);
                    return redirect()->to('admin/' . $this->halaman_controller . '/tambah');
                }
            }
        }
        $data['templateJudul'] = "Halaman Tambah " . $this->halaman_label;

        echo view('admin/v_template_header', $data);
        echo view('admin/v_article_tambah', $data);
        echo view('admin/v_template_footer', $data);
    }

    function edit($post_id)
    {
        $data = [];
        $dataPost = $this->m_posts->getPost($post_id);
        if (empty($dataPost)) {
            return redirect()->to('admin/' . $this->halaman_controller);
        }
        $data = $dataPost;

        if ($this->request->getMethod() == 'post') {
            $data = $this->request->getVar(); #setiap yang diinputkan akan dikembalikan ke view
            $aturan = [
                'post_title' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Judul harus diisi'
                    ],
                ],
                'post_content' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Konten harus diisi'
                    ],
                ],
                'post_thumbnail' => [
                    'rules' => 'is_image[post_thumbnail]',
                    'errors' => [
                        'is_image' => 'Hanya gambar yang diperbolehkan untuk diupload'
                    ]
                ]
            ];
            $file = $this->request->getFile('post_thumbnail');
            if (!$this->validate($aturan)) {
                session()->setFlashdata('warning', $this->validation->getErrors());
            } else {
                $post_thumbnail = $dataPost['post_thumbnail'];
                if ($file->getName()) {
                    $post_thumbnail = $file->getRandomName();
                }
                $record = [
                    'username' => session()->get('akun_username'),
                    'post_title' => $this->request->getVar('post_title'),
                    'post_status' => $this->request->getVar('post_status'),
                    'post_thumbnail' => $post_thumbnail,
                    'post_description' => $this->request->getVar('post_description'),
                    'post_content' => $this->request->getVar('post_content'),
                    'post_id' => $post_id #untuk update perlu tambah post_id sebagai primary key
                ];
                $post_type = $this->halaman_controller;
                $aksi = $this->m_posts->insertPost($record, $post_type);
                if ($aksi != false) {
                    $page_id = $aksi;
                    if ($file->getName()) {
                        if ($dataPost['post_thumbnail']) {
                            @unlink(LOKASI_UPLOAD . "/" . $dataPost['post_thumbnail']);
                        }
                        $lokasi_direktori = LOKASI_UPLOAD; #diambil dari constants
                        $file->move($lokasi_direktori, $post_thumbnail);
                    }
                    session()->setFlashdata('success', 'Data berhasil diperbaiki');
                    return redirect()->to('admin/' . $this->halaman_controller . '/edit/' . $page_id);
                } else {
                    session()->setFlashdata('warning', ['Gagal memperbaiki data']);
                    return redirect()->to('admin/' . $this->halaman_controller . '/edit/' . $page_id);
                }
            }
        }

        $data['templateJudul'] = "Halaman Edit " . $this->halaman_label;

        echo view('admin/v_template_header', $data);
        echo view('admin/v_article_tambah', $data);
        echo view('admin/v_template_footer', $data);
    }
}