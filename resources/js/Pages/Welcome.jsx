export default function Welcome() {
    return (
        <div className="flex flex-col items-center justify-center min-h-screen bg-slate-900">
            <div className="p-8 bg-white rounded-xl shadow-2xl">
                <h1 className="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500 mb-4">
                    Admin Panel TA
                </h1>
                <p className="text-slate-500 font-medium text-center">
                    Tailwind CSS sudah menyala!
                </p>
                <button className="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                    Mulai Jelajahi
                </button>
            </div>
        </div>
    );
}