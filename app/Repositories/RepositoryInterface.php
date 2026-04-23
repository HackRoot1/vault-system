<?php

namespace App\Repositories;

interface RepositoryInterface
{
    public function all();

    public function find($id);

    public function findBy(array $criteria);

    public function findOneBy(array $criteria);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);

    public function paginate($perPage = 15);
}
