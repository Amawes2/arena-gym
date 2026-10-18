import { Outlet } from 'react-router-dom';
import Footer from '../components/public/Footer';
import Navbar from '../components/public/Navbar';
import RouteTransition from '../components/RouteTransition';

export default function PublicLayout() {
    return (
        <div className="min-h-screen bg-elite-bg">
            <Navbar />
            <main>
                <RouteTransition>
                    <Outlet />
                </RouteTransition>
            </main>
            <Footer />
        </div>
    );
}
