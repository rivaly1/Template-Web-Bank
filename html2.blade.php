public function create() {
        return view('admin.add-user');
    }

    public function store(Request $request) {
        $request->merge([
            'password' => Hash::make($request->password)
        ]);

        $user = User::create($request->all());

        if($user) {
            return redirect('home')->with("status", "Success Add User");
        }
        return redirect()->back()->with("status", "Failed add User");
    }

    public function edit(User $user) {
        return view('admin.edit-user', compact('user'));
    }

    public function update(Request $request, User $user) {
        $user->update($request->all());
        return redirect('home')->with("status", "Success Update User");
    }

    public function destroy(User $user) {
        $user->delete();
        return redirect('home')->with("status", "Success Delete User");
    }
