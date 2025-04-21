public function topUp(Request $request) {
    $request->validate([
        'credit' => 'required|numeric|min:1000',
        'user_id' => Auth::user()->role === 'bank' ? 'required|exists:users,id' : ''
    ]);

    $userId = Auth::user()->role === 'bank' ? $request->user_id : Auth::id();
    $description = Auth::user()->role === 'bank' ? 'Top-up oleh Bank' : 'Top-up Saldo';

    Wallet::create([
        'user_id' => $userId,
        'credit' => $request->credit,
        'debit' => 0,
        'status' => 'process',
        'description' => $description
    ]);

    return back()->with('status', 'Top Up Request Processed');
}

public function withdrawal(Request $request) {
    $request->validate([
        'debit' => 'required|numeric|min:1000',
        'recipient_id' => Auth::user()->role === 'bank' ? 'required|exists:users,id' : ''
    ]);

    $userId = Auth::user()->role === 'bank' ? $request->recipient_id : Auth::id();
    $saldo = Wallet::where('user_id', $userId)->where('status', 'done')->sum(DB::raw('credit - debit'));

    if ($saldo < $request->debit) {
        return back()->with('status', 'Saldo kurang buat withdrawal!');
    }

    Wallet::create([
        'user_id' => $userId,
        'credit' => 0,
        'debit' => $request->debit,
        'status' => Auth::user()->role === 'bank' ? 'done' : 'process',
        'description' => Auth::user()->role === 'bank' ? 'Penarikan oleh Bank' : 'Withdraw Saldo'
    ]);

    return back()->with('status', 'Withdrawal Request Processed');
}

public function transfer(Request $request) {
    $request->validate([
        'recipient_id' => 'required|exists:users,id',
        'amount' => 'required|numeric|min:1000'
    ]);

    $sender = Auth::user();
    $recipient = User::findOrFail($request->recipient_id);
    $saldo = Wallet::where('user_id', $sender->id)->where('status', 'done')->sum(DB::raw('credit - debit'));

    if ($saldo < $request->amount) {
        return back()->with('status', 'Saldo kurang ');
    }

    $status = $sender->role === 'bank' ? 'done' : 'process';

    DB::beginTransaction();
    try {
        Wallet::create([
            'user_id' => $sender->id,
            'credit' => 0,
            'debit' => $request->amount,
            'status' => $status,
            'description' => 'Transfer ke ' . $recipient->name
        ]);

        Wallet::create([
            'user_id' => $recipient->id,
            'credit' => $request->amount,
            'debit' => 0,
            'status' => $status,
            'description' => 'Transfer dari ' . $sender->name
        ]);

        DB::commit();
        return back()->with('status', 'Transfer Berhasil!');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('status', 'Terjadi kesalahan, transfer gagal!');
    }
}

public function acceptRequest(Request $request) {
    Wallet::findOrFail($request->wallet_id)->update(['status' => 'done']);
    return back()->with('status', 'Permintaan berhasil disetujui');
}

public function printMutasi() {
    $mutasi = Wallet::where('status', 'done')->with('user')->latest()->get();
    return view('wallet.print', compact('mutasi'));
}

public function printSingle($id) {
    $mutasi = Wallet::findOrFail($id);
    if (Auth::user()->role == 'siswa' && $mutasi->user_id !== Auth::id()) {
        abort(403, 'Anda tidak diizinkan mengakses transaksi ini.');
    }
    return view('wallet.print-single', compact('mutasi'));
}
