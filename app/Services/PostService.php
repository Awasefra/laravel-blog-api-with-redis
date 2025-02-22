<?php

namespace App\Services;

use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Traits\FileUpload;
use App\Traits\RedisTrait;
use Illuminate\Support\Facades\DB;

class PostService
{
    use RedisTrait, FileUpload;

    /**
     * Class constructor.
     */
    public function __construct(protected Post $post) {}

    public function store($request)
    {
        DB::transaction(function () use ($request) {
            // Validasi data yang diterima dari request
            if ($request->hasFile('image')) {
                $url = $this->uploadImageTrait($request->file('image'), '/images/posts', null);
            }

            $dataPost =
                [
                    'title' => $request->title,
                    'content' => $request->content,
                    'image' => $url,
                ];

            // Buat post baru di database
            $post = $this->post->create($dataPost);

            // Cache post baru dan update cache untuk semua post
            $this->cacheData('post', $post);
            $this->updateAllDatasCache($post);
        });
    }

    public function get($id = null)
    {
        // Cek apakah parameter 'id' ada dan tidak null dalam request
        if ($id != null) {
            // Jika 'id' ada, ambil detail 
            return new PostResource($this->post->findOrFail($id));
        } else {
            // Jika 'id' tidak ada, ambil semua data
            return PostResource::collection($this->post->get());
        }
    }

    public function update($request, $id)
    {
        $data = DB::transaction(function () use ($request, $id) {
            // Validasi data yang diterima dari request
            $post = $this->post->findOrFail($id);
            $dataPost =
                [
                    'title' => $request->title,
                    'content' => $request->content,
                ];

            if ($request->hasFile('image')) {
                $url = $this->uploadImageTrait($request->file('image'), '/images/posts', $post->url);

                $dataPost['image'] = $url;
            }

            $post->update($dataPost);

            // Cache post baru dan update cache untuk semua post
            $this->cacheData('post', $post);
            $this->updateAllDatasCache($post);

            return new PostResource($this->post->findOrFail($id));
        });
        return $data;
    }

    public function delete($id)
    {
        DB::transaction(function () use ($id) {
            // Cari post di database
            $post = $this->post->findOrFail($id);

            $deleteStatus = $this->deleteImageByUrl($post->url);

            $post->delete();

            if (!$deleteStatus) {
                DB::rollBack();
                throw new \Exception('file tidak ada');
            }
            // Hapus post dari cache Redis dan update cache untuk semua post
            $this->removeFromCache('name', $id);
            $this->updateAllDatasCache(null, $id);
        });
    }

    // Mengambil semua post dari database dan menyimpannya di cache
    private function fetchDatasFromDatabase()
    {
        $posts = $this->post->get();
        $this->setDatasInCache('all_posts', $posts);
        return $posts;
    }

    // Mengupdate cache untuk semua post, menambahkan atau menghapus post dari cache
    private function updateAllDatasCache($post = null, $removeId = null)
    {
        // Ambil semua post dari cache dan konversi ke array. jika tidak ada maka fetch terlebih dahulu
        $allPosts =  $this->getDatasFromCache('all_posts') ?: $this->fetchDatasFromDatabase();

        // Periksa apakah $allPosts adalah sebuah koleksi Eloquent
        $allPostsArray = $allPosts instanceof \Illuminate\Database\Eloquent\Collection
            // Jika ya, konversi koleksi Eloquent menjadi array
            ? $allPosts->toArray()
            // Jika tidak, biarkan $allPosts tetap sebagai array (misalnya, jika sudah berupa array)
            : $allPosts;


        if ($removeId) {
            $allPostsArray = $this->removeDataAtCache($allPostsArray, $removeId);
        }

        if ($post) {
            $allPostsArray = $this->addOrUpdateAtCache($allPostsArray, $post);
        }

        // Simpan kembali ke cache dan set TTL
        $this->setDatasInCache('all_posts', $allPostsArray);
    }
}
