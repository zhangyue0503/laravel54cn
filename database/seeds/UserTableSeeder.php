<?php

use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		//
		\Illuminate\Support\Facades\DB::table("users")->delete();
		for ($i = 0; $i < 10; $i++) {
			\App\User::create([
				'name' => 'username',
				'email'  => 'account' . $i,
				'password' => 'password' . $i
			]);
		}
	}
}
