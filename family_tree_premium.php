<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Family Tree | Shidhlajury</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #fdfdfd; overflow-x: auto; }

        /* Custom Tree Connectors (Strict Perpendicular Layout) */
        .tree ul {
            padding-top: 20px; position: relative;
            transition: all 0.5s;
            display: flex; justify-content: center;
        }

        .tree li {
            float: left; text-align: center;
            list-style-type: none;
            position: relative;
            padding: 20px 5px 0 5px;
            transition: all 0.5s;
        }

        /* Vertical Line from parent */
        .tree li::before, .tree li::after {
            content: '';
            position: absolute; top: 0; right: 50%;
            border-top: 2px solid #ccc;
            width: 50%; height: 20px;
        }
        .tree li::after {
            right: auto; left: 50%;
            border-left: 2px solid #ccc;
        }

        /* Remove connectors for single/first/last nodes */
        .tree li:only-child::after, .tree li:only-child::before { display: none; }
        .tree li:only-child { padding-top: 0; }
        .tree li:first-child::before, .tree li:last-child::after { border: 0 none; }
        .tree li:last-child::before { border-right: 2px solid #ccc; border-radius: 0 5px 0 0; }
        .tree li:first-child::after { border-radius: 5px 0 0 0; }

        /* Downward line from parent junction */
        .tree ul ul::before {
            content: '';
            position: absolute; top: 0; left: 50%;
            border-left: 2px solid #ccc;
            width: 0; height: 20px;
        }

        /* Red Ribbon Styling */
        .ribbon {
            position: relative;
            background: #ef4444;
            color: white;
            font-size: 0.6rem;
            font-weight: 800;
            padding: 4px 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: -12px;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .ribbon:before, .ribbon:after {
            content: '';
            position: absolute;
            top: 0;
            width: 0; height: 0;
            border-style: solid;
        }
        .ribbon:before {
            left: -8px;
            border-width: 13.5px 8px 13.5px 0;
            border-color: #ef4444 #ef4444 #ef4444 transparent;
        }
        .ribbon:after {
            right: -8px;
            border-width: 13.5px 0 13.5px 8px;
            border-color: #ef4444 transparent #ef4444 #ef4444;
        }

        /* Avatar Ring Glows */
        .ring-gen1 { ring: 4px; ring-color: #f97316; box-shadow: 0 0 15px rgba(249, 115, 22, 0.3); }
        .ring-gen2 { ring: 4px; ring-color: #22c55e; box-shadow: 0 0 15px rgba(34, 197, 94, 0.3); }
        .ring-gen3 { ring: 4px; ring-color: #8b5cf6; box-shadow: 0 0 15px rgba(139, 92, 246, 0.3); }
    </style>
</head>
<body class="p-8">
    <div id="root"></div>

    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

    <script type="text/babel">
        const { useState } = React;

        // 1. Data Structure: Recursive JSON as requested
        const familyData = {
            id: "1",
            label: "DADA JI",
            relationship: "GRANDFATHER",
            gen: 1,
            imageURL: "https://api.dicebear.com/7.x/avataaars/svg?seed=grandpa",
            spouse: {
                label: "DADI JI",
                relationship: "GRANDMOTHER",
                imageURL: "https://api.dicebear.com/7.x/avataaars/svg?seed=grandma"
            },
            children: [
                {
                    id: "2",
                    label: "PAPA",
                    relationship: "FATHER",
                    gen: 2,
                    imageURL: "https://api.dicebear.com/7.x/avataaars/svg?seed=papa",
                    spouse: {
                        label: "MAMMY",
                        relationship: "MOTHER",
                        imageURL: "https://api.dicebear.com/7.x/avataaars/svg?seed=mama"
                    },
                    children: [
                        { id: "4", label: "BIG BRO", relationship: "BROTHER", gen: 3, imageURL: "https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" },
                        { id: "5", label: "ME", relationship: "SELF", gen: 3, imageURL: "https://api.dicebear.com/7.x/avataaars/svg?seed=Me" },
                        { id: "6", label: "SYNERGY", relationship: "SISTER", gen: 3, imageURL: "https://api.dicebear.com/7.x/avataaars/svg?seed=Daisy" }
                    ]
                },
                {
                    id: "3",
                    label: "KAKAJI",
                    relationship: "UNCLE",
                    gen: 2,
                    imageURL: "https://api.dicebear.com/7.x/avataaars/svg?seed=uncle",
                    spouse: {
                        label: "KAKIJI",
                        relationship: "AUNT",
                        imageURL: "https://api.dicebear.com/7.x/avataaars/svg?seed=aunt"
                    },
                    children: [
                        { id: "7", label: "COUSIN A", relationship: "COUSIN", gen: 3, imageURL: "https://api.dicebear.com/7.x/avataaars/svg?seed=7" },
                        { id: "8", label: "COUSIN B", relationship: "COUSIN", gen: 3, imageURL: "https://api.dicebear.com/7.x/avataaars/svg?seed=8" }
                    ]
                }
            ]
        };

        const MemberNode = ({ member }) => {
            const genColors = {
                1: 'border-orange-500',
                2: 'border-green-500',
                3: 'border-purple-500'
            };

            const renderMember = (m) => (
                <div className="flex flex-col items-center">
                    <div className={`w-16 h-16 rounded-full border-4 ${genColors[m.gen || 1]} bg-white p-0.5 shadow-lg overflow-hidden`}>
                        <img src={m.imageURL} alt={m.label} className="w-full h-full object-cover rounded-full" />
                    </div>
                    <div className="ribbon">{m.relationship}</div>
                    <span className="mt-2 font-bold text-gray-800 text-sm uppercase tracking-tighter">{m.label}</span>
                </div>
            );

            return (
                <li>
                    <div className="flex flex-col items-center">
                        <div className="flex items-start gap-4">
                            {renderMember(member)}
                            {member.spouse && (
                                <div className="flex flex-col items-center">
                                    {renderMember(member.spouse)}
                                </div>
                            )}
                        </div>
                        
                        {member.children && member.children.length > 0 && (
                            <ul>
                                {member.children.map(child => (
                                    <MemberNode key={child.id} member={child} />
                                ))}
                            </ul>
                        )}
                    </div>
                </li>
            );
        };

        const App = () => {
            return (
                <div className="min-h-screen flex flex-col items-center justify-start py-20 px-4">
                    <header className="mb-16 text-center">
                        <h1 className="text-4xl font-extrabold text-gray-900 tracking-tight">Our Family Legacy</h1>
                        <p className="text-gray-500 mt-2 font-medium">Building a connected ancestry across generations</p>
                    </header>
                    
                    <div className="tree w-full overflow-x-auto">
                        <ul className="flex justify-center">
                            <MemberNode member={familyData} />
                        </ul>
                    </div>

                    <footer className="mt-32 text-gray-400 text-xs text-center border-t border-gray-100 pt-8 w-full">
                        &copy; 2026 Shidhlajury Village Portal | Powered by Expert React Engine
                    </footer>
                </div>
            );
        };

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
