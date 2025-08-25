import axios from "axios";
import React, { useEffect, useState } from "react";
import { redirectToLogin } from "../../common/redirectToLogin";

interface IAttendanceType {
    inPerson: string;
    online: string;
    either: string;

}

interface ISession {
    sessionId: string;
    title: string;
    rank: string;
    attending: IAttendanceType;
}

interface IParticipantName {
    badgeName: string;
}

interface IParticipant {
    badgeId: string;
    name: IParticipantName;
    isOnlineOnly: boolean;
}

interface ISessionData {
    sessions?: ISession[];
    participants?: IParticipant[];
}

const InitialDataView: React.FC<{}> = () => {

    const [data, setData] = useState<ISessionData>({});

    function fetchData() {
        axios.get('/api/scheduler/initial_data_summary.php')
            .then(res => {
                setData(res.data)
            })
            .catch(error => {
                if (error.response && error.response.status === 401) {
                    redirectToLogin();
                }
            }
        );
    }


    useEffect(() => fetchData(), []);
    const sessionRows = data?.sessions?.map(s => (<tr key={'session-' + s.sessionId}>
                <td>{s.sessionId}</td>
                <td>{s.title}</td>
                <td className="text-center">{s.rank}</td>
                <td className="small text-center">{s.attending?.inPerson}</td>
                <td className="small text-center">{s.attending?.online}</td>
                <td className="small text-center">{s.attending?.either}</td>
            </tr>));

    return (<>
            <h3>Sessions</h3>
            <table className="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Title</th>
                        <th className="text-center">Rank</th>
                        <th className="text-center">In-Person</th>
                        <th className="text-center">Online</th>
                        <th className="text-center">Either</th>
                    </tr>
                </thead>
                <tbody>
                    {sessionRows}
                </tbody>
            </table>

            <h3 className="mt-4">Potential Participants</h3>
            <table className="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Badge Id</th>
                        <th>Name</th>
                        <th className="text-center">Online?</th>
                    </tr>
                </thead>
                <tbody>
                    {data?.participants?.map(p => (<tr key={'participant-' + p.badgeId}>
                        <td>{p.badgeId}</td>
                        <td>{p.name?.badgeName}</td>
                        <td className="text-center">{p.isOnlineOnly ? 'Yes' : 'No'}</td>
                    </tr>))}
                </tbody>
            </table>
    </>);
}

export default InitialDataView;