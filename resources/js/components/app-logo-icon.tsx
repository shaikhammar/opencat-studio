import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="-50 -50 100 100" xmlns="http://www.w3.org/2000/svg">
            <polygon points="0,-46 46,0 0,46 -46,0" fill="currentColor" />
            <rect x="-10" y="-14" width="20" height="3" rx="1.5" fill="white" opacity="0.55" />
            <rect x="-10" y="11" width="20" height="3" rx="1.5" fill="white" opacity="0.55" />
        </svg>
    );
}
